<?php

namespace Modules\Sales\Controllers\Web;

use Modules\Finance\Controllers\Web\SalesDocumentPdfController;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;
use Symfony\Component\HttpFoundation\Response;

/**
 * Printable quotations and order confirmations, in the same layout as invoices.
 */
class SalesPdfController extends SalesDocumentPdfController
{
    public function quotation(int $id): Response
    {
        $quotation = Quotation::with(['company.baseCurrency', 'customer', 'currency', 'lines.tax'])->findOrFail($id);

        return $this->render($quotation, [
            'title' => __('Quotation'),
            'number' => $quotation->quotation_number,
            'date' => $quotation->quotation_date,
            'dueDate' => $quotation->valid_until,
            'dueDateLabel' => __('Valid until'),
            'reference' => $quotation->customer_reference,
            'referenceLabel' => __('Your reference'),
            'totalLabel' => __('Total'),
            'isFinal' => $quotation->status !== Quotation::STATUS_DRAFT,
            'notes' => $quotation->notes,
        ]);
    }

    public function order(int $id): Response
    {
        $order = SalesOrder::with(['company.baseCurrency', 'customer', 'currency', 'lines.tax'])->findOrFail($id);

        return $this->render($order, [
            'title' => __('Order confirmation'),
            'number' => $order->order_number,
            'date' => $order->order_date,
            'dueDate' => $order->delivery_date,
            'dueDateLabel' => __('Delivery date'),
            'reference' => $order->customer_reference,
            'referenceLabel' => __('Your reference'),
            'totalLabel' => __('Total'),
            'isFinal' => $order->status !== SalesOrder::STATUS_DRAFT,
            'notes' => $order->notes,
        ]);
    }
}
