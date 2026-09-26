<?php

namespace Modules\Sales\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Finance\Exceptions\MissingExchangeRateException;
use Modules\Finance\Models\CustomerCreditNoteLine;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\CustomerInvoiceLine;
use Modules\Finance\Services\ExchangeRateService;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;

/**
 * Figures for the sales dashboard, in the company's functional currency. Sales and margins come from posted
 * customer invoices less posted credit notes (net of tax); cost is the cost of goods sold recorded on them.
 */
class SalesDashboardService
{
    /**
     * Invoices that count as sales.
     */
    public const SALES_STATUSES = [CustomerInvoice::STATUS_POSTED, CustomerInvoice::STATUS_PARTIALLY_PAID, CustomerInvoice::STATUS_PAID];

    /**
     * Documents whose currency had no exchange rate, left out of the pipeline figures.
     */
    protected int $unconverted = 0;

    /**
     * @var array<string, string|null>
     */
    protected array $rates = [];

    public function __construct(protected ExchangeRateService $exchangeRates) {}

    /**
     * @return array{open_quotations: int, quotation_value: string, backlog_value: string, to_invoice_value: string, sales_this_month: string, margin_this_month: string, margin_percent: string|null, unconverted: int}
     */
    public function summary(int $companyId, CarbonImmutable $today): array
    {
        $this->unconverted = 0;
        $quotations = Quotation::whereIn('status', [Quotation::STATUS_DRAFT, Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])->get();
        $orders = SalesOrder::with('lines.product')->whereIn('status', SalesOrder::OPEN_STATUSES)->get();

        $backlog = '0';
        $toInvoice = '0';

        foreach ($orders as $order) {
            $rate = $this->rate($companyId, $order->currency_id, $order->order_date);

            if ($rate === null) {
                continue;
            }

            foreach ($order->lines as $line) {
                $unitNet = bccomp((string) $line->quantity, '0', 4) > 0 ? bcdiv((string) $line->subtotal, (string) $line->quantity, 8) : '0';
                $undelivered = $line->needsDelivery() ? $line->undeliveredQuantity() : bcsub((string) $line->quantity, (string) $line->invoiced_quantity, 4);
                $backlog = bcadd($backlog, bcmul(bcmul($unitNet, $this->positive($undelivered), 8), $rate, 8), 8);
                $toInvoice = bcadd($toInvoice, bcmul(bcmul($unitNet, $this->positive($line->needsDelivery() ? $line->billableQuantity() : '0'), 8), $rate, 8), 8);
            }
        }

        $quotationValue = $quotations->reduce(function (string $sum, Quotation $quotation) use ($companyId) {
            $rate = $this->rate($companyId, $quotation->currency_id, $quotation->quotation_date);

            return $rate === null ? $sum : bcadd($sum, bcmul((string) $quotation->subtotal, $rate, 8), 8);
        }, '0');

        $month = $this->monthlySales($today->startOfMonth(), $today)->first() ?? ['revenue' => '0', 'margin' => '0'];

        return [
            'open_quotations' => $quotations->count(),
            'quotation_value' => $quotationValue,
            'backlog_value' => $backlog,
            'to_invoice_value' => $toInvoice,
            'sales_this_month' => $month['revenue'],
            'margin_this_month' => $month['margin'],
            'margin_percent' => bccomp($month['revenue'], '0', 4) !== 0 ? bcmul(bcdiv($month['margin'], $month['revenue'], 8), '100', 2) : null,
            'unconverted' => $this->unconverted,
        ];
    }

    /**
     * Sales, cost and margin per month from $from to $to, keyed Y-m (oldest first).
     *
     * @return Collection<string, array{label: string, revenue: string, cost: string, margin: string}>
     */
    public function monthlySales(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $months = collect();

        for ($month = $from->startOfMonth(); $month->lte($to); $month = $month->addMonth()) {
            $months[$month->format('Y-m')] = ['label' => $month->translatedFormat('M Y'), 'revenue' => '0', 'cost' => '0', 'margin' => '0'];
        }

        foreach ($this->salesLines($from, $to) as $line) {
            $key = $line['date']->format('Y-m');

            if (! $months->has($key)) {
                continue;
            }

            $row = $months[$key];
            $row['revenue'] = bcadd($row['revenue'], $line['revenue'], 8);
            $row['cost'] = bcadd($row['cost'], $line['cost'], 8);
            $row['margin'] = bcsub($row['revenue'], $row['cost'], 8);
            $months[$key] = $row;
        }

        return $months;
    }

    /**
     * Customers with the highest sales in the period.
     *
     * @return Collection<int, array{name: string, revenue: string, margin: string}>
     */
    public function topCustomers(CarbonImmutable $from, CarbonImmutable $to, int $limit = 5): Collection
    {
        return $this->salesLines($from, $to)
            ->groupBy('customer')
            ->map(fn (Collection $lines, string $name) => $this->totals($lines) + ['name' => $name])
            ->sortByDesc(fn (array $row) => (float) $row['revenue'])
            ->take($limit)
            ->values();
    }

    /**
     * Products with the highest gross margin in the period.
     *
     * @return Collection<int, array{name: string, revenue: string, margin: string, margin_percent: string|null}>
     */
    public function topProducts(CarbonImmutable $from, CarbonImmutable $to, int $limit = 5): Collection
    {
        return $this->salesLines($from, $to)
            ->filter(fn (array $line) => $line['product'] !== null)
            ->groupBy('product')
            ->map(function (Collection $lines, string $name) {
                $totals = $this->totals($lines);

                return $totals + ['name' => $name, 'margin_percent' => bccomp($totals['revenue'], '0', 4) !== 0 ? bcmul(bcdiv($totals['margin'], $totals['revenue'], 8), '100', 2) : null];
            })
            ->sortByDesc(fn (array $row) => (float) $row['margin'])
            ->take($limit)
            ->values();
    }

    /**
     * Open orders with goods still to deliver by the given date, earliest first.
     *
     * @return Collection<int, SalesOrder>
     */
    public function dueForDelivery(CarbonImmutable $until, int $limit = 8): Collection
    {
        return SalesOrder::with(['customer', 'lines.product'])
            ->whereIn('status', [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_PARTIALLY_DELIVERED])
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '<=', $until->toDateString())
            ->orderBy('delivery_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Net sales lines of posted invoices and credit notes in the period, in the functional currency.
     *
     * @return Collection<int, array{date: CarbonImmutable, customer: string, product: string|null, revenue: string, cost: string}>
     */
    protected function salesLines(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $invoiceLines = CustomerInvoiceLine::with(['invoice.customer', 'product'])
            ->whereIn('customer_invoice_id', CustomerInvoice::whereIn('status', self::SALES_STATUSES)
                ->whereDate('invoice_date', '>=', $from->toDateString())
                ->whereDate('invoice_date', '<=', $to->toDateString())
                ->select('id'))
            ->get()
            ->map(fn (CustomerInvoiceLine $line) => [
                'date' => CarbonImmutable::parse($line->invoice->invoice_date),
                'customer' => (string) $line->invoice->customer?->name,
                'product' => $line->product ? "{$line->product->sku} — {$line->product->name}" : null,
                'revenue' => bcmul((string) $line->subtotal, (string) ($line->invoice->exchange_rate ?: 1), 8),
                'cost' => (string) ($line->cost_value ?? '0'),
            ]);

        $creditLines = CustomerCreditNoteLine::with(['creditNote.customer', 'product'])
            ->whereHas('creditNote', fn ($query) => $query->where('status', 'POSTED')
                ->whereDate('note_date', '>=', $from->toDateString())
                ->whereDate('note_date', '<=', $to->toDateString()))
            ->get()
            ->map(fn ($line) => [
                'date' => CarbonImmutable::parse($line->creditNote->note_date),
                'customer' => (string) $line->creditNote->customer?->name,
                'product' => $line->product ? "{$line->product->sku} — {$line->product->name}" : null,
                'revenue' => bcmul(bcmul((string) $line->subtotal, (string) ($line->creditNote->exchange_rate ?: 1), 8), '-1', 8),
                'cost' => bcmul((string) ($line->cost_value ?? '0'), '-1', 8),
            ]);

        return $invoiceLines->concat($creditLines)->values();
    }

    /**
     * @param  Collection<int, array{revenue: string, cost: string}>  $lines
     * @return array{revenue: string, cost: string, margin: string}
     */
    protected function totals(Collection $lines): array
    {
        $revenue = $lines->reduce(fn (string $sum, array $line) => bcadd($sum, $line['revenue'], 8), '0');
        $cost = $lines->reduce(fn (string $sum, array $line) => bcadd($sum, $line['cost'], 8), '0');

        return ['revenue' => $revenue, 'cost' => $cost, 'margin' => bcsub($revenue, $cost, 8)];
    }

    protected function rate(int $companyId, ?int $currencyId, mixed $date): ?string
    {
        $key = $currencyId.'@'.CarbonImmutable::parse($date)->toDateString();

        if (! array_key_exists($key, $this->rates)) {
            try {
                $this->rates[$key] = $this->exchangeRates->rateForDocument($companyId, $currencyId, $date);
            } catch (MissingExchangeRateException) {
                $this->rates[$key] = null;
            }
        }

        if ($this->rates[$key] === null) {
            $this->unconverted++;
        }

        return $this->rates[$key];
    }

    protected function positive(string $quantity): string
    {
        return bccomp($quantity, '0', 4) > 0 ? $quantity : '0';
    }
}
