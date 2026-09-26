<?php

namespace Modules\Finance\Controllers\Web;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\CustomerCreditNote;
use Modules\Finance\Models\CustomerInvoice;
use Symfony\Component\HttpFoundation\Response;

/**
 * Printable PDFs of sales documents. Documents not yet posted carry a DRAFT watermark.
 */
class SalesDocumentPdfController extends Controller
{
    public function invoice(int $id): Response
    {
        $invoice = CustomerInvoice::with(['company.baseCurrency', 'customer', 'currency', 'lines.tax'])->findOrFail($id);

        return $this->render($invoice, [
            'title' => __('Tax invoice'),
            'number' => $invoice->invoice_number,
            'date' => $invoice->invoice_date,
            'dueDate' => $invoice->due_date,
            'reference' => null,
            'totalLabel' => __('Total due'),
            'isFinal' => in_array($invoice->status, [CustomerInvoice::STATUS_POSTED, CustomerInvoice::STATUS_PARTIALLY_PAID, CustomerInvoice::STATUS_PAID], true),
            'notes' => $invoice->description,
        ]);
    }

    public function creditNote(int $id): Response
    {
        $creditNote = CustomerCreditNote::with(['company.baseCurrency', 'customer', 'currency', 'invoice', 'lines.tax'])->findOrFail($id);

        return $this->render($creditNote, [
            'title' => __('Credit note'),
            'number' => $creditNote->note_number,
            'date' => $creditNote->note_date,
            'dueDate' => null,
            'reference' => $creditNote->invoice?->invoice_number,
            'totalLabel' => __('Total credited'),
            'isFinal' => $creditNote->isPosted(),
            'notes' => $creditNote->description,
        ]);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function render(Model $document, array $details): Response
    {
        $view = [
            ...$details,
            'document' => $document,
            'company' => $document->company,
            'party' => $document->customer,
            'lines' => $document->lines,
            'currencyCode' => $document->currency?->code ?? $document->company->baseCurrency?->code ?? 'XXX',
            'taxSummary' => $this->taxSummary($document->lines),
        ];

        $pdf = Pdf::loadView('finance.pdf.sales-document', $view)->setPaper('a4');
        $pdf->render();

        $canvas = $pdf->getDomPDF()->getCanvas();
        $canvas->page_text($canvas->get_width() - 70, $canvas->get_height() - 30, '{PAGE_NUM} / {PAGE_COUNT}', null, 7, [0.47, 0.47, 0.47]);

        return $pdf->stream(str_replace(['/', '\\'], '-', $details['number']).'.pdf');
    }

    /**
     * Taxable amount and tax per tax code, as tax authorities require on a tax invoice.
     *
     * @return Collection<int, array{code: string, rate: string, base: string, tax: string}>
     */
    protected function taxSummary(Collection $lines): Collection
    {
        return $lines->filter(fn ($line) => $line->tax)
            ->groupBy('tax_id')
            ->map(fn (Collection $group) => [
                'code' => $group->first()->tax->tax_code,
                'rate' => (string) $group->first()->tax->rate,
                'base' => $group->reduce(fn (string $sum, $line) => bcadd($sum, (string) $line->subtotal, 4), '0'),
                'tax' => $group->reduce(fn (string $sum, $line) => bcadd($sum, (string) $line->tax_amount, 4), '0'),
            ])
            ->values();
    }
}
