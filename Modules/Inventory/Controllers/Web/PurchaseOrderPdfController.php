<?php

namespace Modules\Inventory\Controllers\Web;

use Modules\Finance\Controllers\Web\SalesDocumentPdfController;
use Modules\Inventory\Models\PurchaseOrder;
use Symfony\Component\HttpFoundation\Response;

/**
 * The purchase order as sent to the supplier, in the same layout as sales documents.
 */
class PurchaseOrderPdfController extends SalesDocumentPdfController
{
    public function __invoke(int $id): Response
    {
        $order = PurchaseOrder::with(['company.baseCurrency', 'supplier', 'currency', 'warehouse', 'lines.tax'])->findOrFail($id);
        $deliverTo = collect([$order->warehouse?->name, $order->warehouse?->address])->filter()->implode(', ');

        return $this->render($order, [
            'title' => __('Purchase order'),
            'number' => $order->order_number,
            'date' => $order->order_date,
            'dueDate' => $order->expected_date,
            'dueDateLabel' => __('Expected delivery'),
            'reference' => $order->supplier_reference,
            'referenceLabel' => __('Your reference'),
            'party' => $order->supplier,
            'partyLabel' => __('Supplier'),
            'totalLabel' => __('Total'),
            'isFinal' => ! in_array($order->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SUBMITTED, PurchaseOrder::STATUS_REJECTED], true),
            'notes' => trim(($deliverTo !== '' ? __('Deliver to: :address', ['address' => $deliverTo])."\n" : '').($order->notes ?? '')),
        ]);
    }
}
