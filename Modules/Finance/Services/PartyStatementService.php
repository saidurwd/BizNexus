<?php

namespace Modules\Finance\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerCreditNote;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\CustomerReceipt;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierCreditNote;
use Modules\Finance\Models\SupplierDebitNote;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierPayment;

/**
 * Statements of account for customers and suppliers from posted documents only, in the functional currency
 * (each document at its own exchange rate). "Debit" raises the balance owed, "credit" lowers it.
 */
class PartyStatementService
{
    protected const POSTED_INVOICE_STATUSES = ['POSTED', 'PARTIALLY_PAID', 'PAID'];

    /**
     * @return array{opening_balance: string, closing_balance: string, total_invoices: string, total_receipts: string, total_credits: string, entries: list<array<string, mixed>>}
     */
    public function forCustomer(Customer $customer, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $sources = [
            ['invoice', CustomerInvoice::where('customer_id', $customer->id)->whereIn('status', self::POSTED_INVOICE_STATUSES), 'invoice_date', 'invoice_number', 'total_amount', 'debit'],
            ['credit_note', CustomerCreditNote::where('customer_id', $customer->id)->where('status', CustomerCreditNote::STATUS_POSTED), 'note_date', 'note_number', 'total_amount', 'credit'],
            ['receipt', CustomerReceipt::where('customer_id', $customer->id)->where('status', CustomerReceipt::STATUS_POSTED), 'receipt_date', 'receipt_number', 'amount', 'credit'],
        ];

        $statement = $this->build($sources, $from, $to);

        return [...$statement, 'total_receipts' => $statement['totals']['receipt'], 'total_invoices' => $statement['totals']['invoice'], 'total_credits' => $statement['totals']['credit_note']];
    }

    /**
     * @return array{opening_balance: string, closing_balance: string, total_invoices: string, total_payments: string, total_credits: string, entries: list<array<string, mixed>>}
     */
    public function forSupplier(Supplier $supplier, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $posted = ['POSTED', 'posted'];
        $sources = [
            ['invoice', SupplierInvoice::where('supplier_id', $supplier->id)->whereIn('status', self::POSTED_INVOICE_STATUSES), 'invoice_date', 'invoice_number', 'total_amount', 'debit'],
            ['credit_note', SupplierCreditNote::where('supplier_id', $supplier->id)->whereIn('status', $posted), 'credit_note_date', 'credit_note_number', 'total_amount', 'credit'],
            ['debit_note', SupplierDebitNote::where('supplier_id', $supplier->id)->whereIn('status', $posted), 'note_date', 'note_number', 'amount', 'credit'],
            ['payment', SupplierPayment::where('supplier_id', $supplier->id)->where('status', SupplierPayment::STATUS_POSTED), 'payment_date', 'payment_number', 'amount', 'credit'],
        ];

        $statement = $this->build($sources, $from, $to);

        return [
            ...$statement,
            'total_invoices' => $statement['totals']['invoice'],
            'total_payments' => $statement['totals']['payment'],
            'total_credits' => bcadd($statement['totals']['credit_note'], $statement['totals']['debit_note'], 4),
        ];
    }

    /**
     * @param  list<array{0: string, 1: Builder, 2: string, 3: string, 4: string, 5: string}>  $sources  type, query, date column, number column, amount column, side
     * @return array{opening_balance: string, closing_balance: string, totals: array<string, string>, entries: list<array<string, mixed>>}
     */
    protected function build(array $sources, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $opening = '0.0000';
        $entries = collect();
        $totals = [];

        foreach ($sources as [$type, $query, $dateColumn, $numberColumn, $amountColumn, $side]) {
            $totals[$type] = '0.0000';

            if ($from) {
                $before = $this->functionalTotal((clone $query)->whereDate($dateColumn, '<', $from->toDateString())->get(), $amountColumn);
                $opening = $side === 'debit' ? bcadd($opening, $before, 4) : bcsub($opening, $before, 4);
            }

            $documents = (clone $query)
                ->when($from, fn ($q) => $q->whereDate($dateColumn, '>=', $from->toDateString()))
                ->when($to, fn ($q) => $q->whereDate($dateColumn, '<=', $to->toDateString()))
                ->get();

            foreach ($documents as $document) {
                $amount = $this->functional($document, $amountColumn);
                $totals[$type] = bcadd($totals[$type], $amount, 4);
                $entries->push([
                    'date' => $document->{$dateColumn},
                    'type' => $type,
                    'number' => $document->{$numberColumn},
                    'description' => $document->description ?? $document->reason ?? null,
                    'debit' => $side === 'debit' ? $amount : '0',
                    'credit' => $side === 'credit' ? $amount : '0',
                ]);
            }
        }

        $balance = $opening;
        $entries = $entries
            ->sortBy(fn (array $entry) => [$entry['date']?->toDateString(), $entry['type'] === 'invoice' ? 0 : 1, $entry['number']])
            ->map(function (array $entry) use (&$balance) {
                $balance = bcsub(bcadd($balance, $entry['debit'], 4), $entry['credit'], 4);

                return [...$entry, 'balance' => $balance];
            })
            ->values()
            ->all();

        return ['opening_balance' => $opening, 'closing_balance' => $balance, 'totals' => $totals, 'entries' => $entries];
    }

    /**
     * @param  Collection<int, Model>  $documents
     */
    protected function functionalTotal(Collection $documents, string $amountColumn): string
    {
        return $documents->reduce(fn (string $total, $document) => bcadd($total, $this->functional($document, $amountColumn), 4), '0.0000');
    }

    protected function functional($document, string $amountColumn): string
    {
        return bcmul((string) $document->{$amountColumn}, (string) ($document->exchange_rate ?: 1), 4);
    }
}
