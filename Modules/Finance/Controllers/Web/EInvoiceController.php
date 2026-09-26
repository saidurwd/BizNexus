<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Services\EInvoiceService;

class EInvoiceController extends Controller
{
    public function show(Request $request, int $id, EInvoiceService $eInvoices)
    {
        $invoice = CustomerInvoice::with('lines')->findOrFail($id);

        abort_unless(in_array($invoice->status, [CustomerInvoice::STATUS_POSTED, CustomerInvoice::STATUS_PARTIALLY_PAID, CustomerInvoice::STATUS_PAID], true), 422, 'Only posted invoices can be issued electronically.');

        try {
            $format = $eInvoices->formatFor($invoice, $request->query('format'));
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return response($format->render($invoice), 200, [
            'Content-Type' => $format->mimeType(),
            'Content-Disposition' => 'attachment; filename="'.$invoice->invoice_number.'.'.$format->fileExtension().'"',
        ]);
    }
}
