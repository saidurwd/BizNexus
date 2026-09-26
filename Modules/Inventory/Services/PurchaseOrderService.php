<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\ApprovalNotifier;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Support\Money;
use Modules\Finance\Contracts\TaxCalculator;
use Modules\Finance\Models\Tax;
use Modules\Finance\Services\Concerns\EnforcesSegregationOfDuties;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\PurchaseOrder;

/**
 * Purchase orders: drafted, approved by someone other than their creator, then received and invoiced.
 * Tax on an order is an estimate from the lines' tax codes; the supplier invoice carries the tax that posts.
 */
class PurchaseOrderService
{
    use EnforcesSegregationOfDuties;

    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected TaxCalculator $taxCalculator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                ...$this->headerAttributes($data),
                'company_id' => $data['company_id'],
                'order_number' => $this->documentNumber->generateNumber($data['company_id'], 'PO'),
                'status' => PurchaseOrder::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            $this->replaceLines($order, $data['lines']);
            $this->audit->logCreate('Inventory', 'PurchaseOrder', $order->id, $order->toArray());

            return $order->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PurchaseOrder $order, array $data): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_REJECTED], 'edited');

        return DB::transaction(function () use ($order, $data) {
            $order->update([...$this->headerAttributes($data), 'status' => PurchaseOrder::STATUS_DRAFT, 'rejection_reason' => null, 'updated_by' => Auth::id()]);
            $this->replaceLines($order, $data['lines']);

            return $order->fresh();
        });
    }

    public function submit(PurchaseOrder $order): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::STATUS_DRAFT], 'submitted');

        if ($order->lines()->doesntExist()) {
            throw new InvalidAccountingTransactionException(__('An order needs at least one line.'));
        }

        $submitted = $this->transition($order, PurchaseOrder::STATUS_SUBMITTED, 'SUBMIT');
        app(ApprovalNotifier::class)->documentSubmitted($submitted->company_id, 'inventory.purchase-orders.approve', __('Purchase order'), $submitted->order_number, route('inventory.purchase-orders.show', $submitted->id), (string) $submitted->total_amount, $submitted->currency?->code);

        return $submitted;
    }

    public function approve(PurchaseOrder $order): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::STATUS_SUBMITTED], 'approved');
        $this->ensureApproverIsNotCreator($order, 'purchase order');

        $order->approved_by = Auth::id();
        $order->approved_at = now();

        return $this->transition($order, PurchaseOrder::STATUS_APPROVED, 'APPROVE');
    }

    public function reject(PurchaseOrder $order, ?string $reason): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::STATUS_SUBMITTED], 'rejected');

        $order->rejection_reason = $reason;

        return $this->transition($order, PurchaseOrder::STATUS_REJECTED, 'REJECT', ['reason' => $reason]);
    }

    /**
     * Cancel an order nothing has been received against yet.
     */
    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SUBMITTED, PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_REJECTED], 'cancelled');

        return $this->transition($order, PurchaseOrder::STATUS_CANCELLED, 'CANCEL');
    }

    /**
     * Close a partly received order: the rest will not be delivered. Goods already received stay to be invoiced.
     */
    public function close(PurchaseOrder $order): PurchaseOrder
    {
        $this->requireStatus($order, [PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_RECEIVED], 'closed');

        if ($order->load('lines')->hasUninvoicedReceipts()) {
            throw new InvalidAccountingTransactionException(__('Goods received on this order are still waiting for the supplier\'s invoice.'));
        }

        if ($order->hasCreditDue()) {
            throw new InvalidAccountingTransactionException(__('Returned goods on this order are still waiting for the supplier\'s credit note.'));
        }

        return $this->transition($order, PurchaseOrder::STATUS_CLOSED, 'CLOSE');
    }

    public function delete(PurchaseOrder $order): void
    {
        $this->requireStatus($order, [PurchaseOrder::STATUS_DRAFT], 'deleted');

        DB::transaction(function () use ($order) {
            $this->audit->logDelete('Inventory', 'PurchaseOrder', $order->id, $order->toArray());
            $order->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function headerAttributes(array $data): array
    {
        return [
            'supplier_id' => $data['supplier_id'],
            'supplier_reference' => $data['supplier_reference'] ?? null,
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'] ?? null,
            'warehouse_id' => $data['warehouse_id'],
            'currency_id' => $data['currency_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function replaceLines(PurchaseOrder $order, array $lines): void
    {
        $order->lines()->delete();
        $currency = $order->fresh()->currencyCode();
        $subtotal = Money::zero($currency);
        $tax = Money::zero($currency);

        foreach ($lines as $line) {
            $product = Product::findOrFail($line['product_id']);
            $net = Money::of($line['quantity'], $currency)->multipliedBy((string) $line['unit_price']);
            $taxCode = isset($line['tax_id']) ? Tax::find($line['tax_id']) : null;
            $lineTax = $taxCode ? $this->taxCalculator->calculate($taxCode, $net, $order->order_date)->totalTax() : Money::zero($currency);

            $order->lines()->create([
                'product_id' => $product->id,
                'description' => $line['description'] ?? $product->name,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_id' => $taxCode?->id,
                'subtotal' => $net->amount,
                'tax_amount' => $lineTax->amount,
                'total_amount' => $net->plus($lineTax)->amount,
            ]);

            $subtotal = $subtotal->plus($net);
            $tax = $tax->plus($lineTax);
        }

        $order->update(['subtotal' => $subtotal->amount, 'tax_amount' => $tax->amount, 'total_amount' => $subtotal->plus($tax)->amount]);
    }

    /**
     * @param  list<string>  $allowed
     */
    protected function requireStatus(PurchaseOrder $order, array $allowed, string $action): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw new InvalidAccountingTransactionException(__('This is not possible while the purchase order is :status.', ['status' => strtolower(str_replace('_', ' ', $order->status))]));
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function transition(PurchaseOrder $order, string $status, string $action, array $context = []): PurchaseOrder
    {
        $previous = $order->status;
        $order->status = $status;
        $order->updated_by = Auth::id();
        $order->save();

        $this->audit->logCustom('Inventory', 'PurchaseOrder', $order->id, $action, ['previous_status' => $previous, ...$context]);

        return $order->fresh();
    }
}
