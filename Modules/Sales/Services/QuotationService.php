<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\Concerns\PricesSalesLines;

/**
 * Quotations: drafted, sent, then accepted or declined by the customer. An accepted (or still open) quotation
 * is turned into a sales order with the same lines.
 */
class QuotationService
{
    use PricesSalesLines;

    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected SalesOrderService $orders,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Quotation
    {
        return DB::transaction(function () use ($data) {
            $quotation = Quotation::create([
                ...$this->headerAttributes($data),
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'quotation_number' => $this->documentNumber->generateNumber($data['company_id'], 'QT'),
                'status' => Quotation::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            $this->replacePricedLines($quotation, $data['lines'], $quotation->currencyCode(), $quotation->quotation_date);
            $this->audit->logCreate('Sales', 'Quotation', $quotation->id, $quotation->toArray());

            return $quotation->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Quotation $quotation, array $data): Quotation
    {
        $this->requireStatus($quotation, [Quotation::STATUS_DRAFT, Quotation::STATUS_SENT]);

        return DB::transaction(function () use ($quotation, $data) {
            $quotation->update([...$this->headerAttributes($data), 'updated_by' => Auth::id()]);
            $quotation->refresh();
            $this->replacePricedLines($quotation, $data['lines'], $quotation->currencyCode(), $quotation->quotation_date);

            return $quotation->fresh();
        });
    }

    public function markSent(Quotation $quotation): Quotation
    {
        $this->requireStatus($quotation, [Quotation::STATUS_DRAFT]);

        return $this->transition($quotation, Quotation::STATUS_SENT, 'SEND');
    }

    public function accept(Quotation $quotation): Quotation
    {
        $this->requireStatus($quotation, [Quotation::STATUS_DRAFT, Quotation::STATUS_SENT]);

        return $this->transition($quotation, Quotation::STATUS_ACCEPTED, 'ACCEPT');
    }

    public function decline(Quotation $quotation): Quotation
    {
        $this->requireStatus($quotation, [Quotation::STATUS_DRAFT, Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED]);

        return $this->transition($quotation, Quotation::STATUS_DECLINED, 'DECLINE');
    }

    public function delete(Quotation $quotation): void
    {
        $this->requireStatus($quotation, [Quotation::STATUS_DRAFT]);
        $this->audit->logDelete('Sales', 'Quotation', $quotation->id, $quotation->toArray());
        $quotation->delete();
    }

    /**
     * Turn the quotation into a draft sales order with the same customer, prices and lines.
     *
     * @param  array{order_date: string, warehouse_id: int, delivery_date?: string|null}  $data
     */
    public function convert(Quotation $quotation, array $data): SalesOrder
    {
        if (! $quotation->canConvert()) {
            throw new InvalidAccountingTransactionException(__('Only open or accepted quotations can become sales orders.'));
        }

        return DB::transaction(function () use ($quotation, $data) {
            $order = $this->orders->create([
                'company_id' => $quotation->company_id,
                'branch_id' => $quotation->branch_id,
                'customer_id' => $quotation->customer_id,
                'sales_quotation_id' => $quotation->id,
                'customer_reference' => $quotation->customer_reference,
                'order_date' => $data['order_date'],
                'delivery_date' => $data['delivery_date'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'currency_id' => $quotation->currency_id,
                'notes' => $quotation->notes,
                'lines' => $quotation->lines->map(fn ($line) => $line->only(['product_id', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_id']))->all(),
            ]);

            $this->transition($quotation, Quotation::STATUS_CONVERTED, 'CONVERT', ['sales_order_id' => $order->id]);

            return $order;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function headerAttributes(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'],
            'customer_reference' => $data['customer_reference'] ?? null,
            'quotation_date' => $data['quotation_date'],
            'valid_until' => $data['valid_until'] ?? null,
            'currency_id' => $data['currency_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * @param  list<string>  $allowed
     */
    protected function requireStatus(Quotation $quotation, array $allowed): void
    {
        if (! in_array($quotation->status, $allowed, true)) {
            throw new InvalidAccountingTransactionException(__('This is not possible while the quotation is :status.', ['status' => strtolower($quotation->status)]));
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function transition(Quotation $quotation, string $status, string $action, array $context = []): Quotation
    {
        $previous = $quotation->status;
        $quotation->update(['status' => $status, 'updated_by' => Auth::id()]);
        $this->audit->logCustom('Sales', 'Quotation', $quotation->id, $action, ['previous_status' => $previous, ...$context]);

        return $quotation->fresh();
    }
}
