<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Services\AuditService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\CustomerCreditNote;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\CustomerReceipt;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\SupplierCreditNote;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierPayment;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Supporting files on finance documents, stored privately and served only through the application. Viewing a
 * file needs view rights on its document, attaching one needs create rights; a file can be removed only by the
 * person who uploaded it while the document is still a draft or rejected.
 */
class AttachmentController extends Controller
{
    /**
     * @var array<string, class-string<Model>>
     */
    public const DOCUMENTS = [
        'customer-invoices' => CustomerInvoice::class,
        'supplier-invoices' => SupplierInvoice::class,
        'customer-credit-notes' => CustomerCreditNote::class,
        'supplier-credit-notes' => SupplierCreditNote::class,
        'journals' => Journal::class,
        'payments' => SupplierPayment::class,
        'receipts' => CustomerReceipt::class,
    ];

    public const ALLOWED_TYPES = 'pdf,jpg,jpeg,png,webp,gif,heic,xlsx,xls,csv,docx,doc,odt,ods,txt,xml,zip,eml';

    public const MAX_KILOBYTES = 10240;

    public function store(Request $request, int $id, AuditService $audit): RedirectResponse
    {
        $documentType = $request->route('documentType');
        $document = $this->document($documentType, $id);
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, 'mimes:'.self::ALLOWED_TYPES],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $file = $validated['file'];

        $attachment = $document->attachments()->create([
            'company_id' => $document->company_id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store("attachments/{$document->company_id}/{$documentType}", 'local'),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'description' => $validated['description'] ?? null,
        ]);

        $audit->logCustom('Finance', class_basename($document), $document->id, 'ATTACH', ['attachment_id' => $attachment->id, 'file_name' => $attachment->file_name]);

        return back()->with('success', __('File attached.'));
    }

    public function download(Request $request, int $id, int $attachment): StreamedResponse
    {
        $file = $this->document($request->route('documentType'), $id)->attachments()->findOrFail($attachment);

        return Storage::disk('local')->download($file->file_path, $file->file_name);
    }

    public function destroy(Request $request, int $id, int $attachment, AuditService $audit): RedirectResponse
    {
        $document = $this->document($request->route('documentType'), $id);
        $file = $document->attachments()->findOrFail($attachment);

        if ((int) $file->uploaded_by !== (int) $request->user()->id || ! in_array($document->status, ['DRAFT', 'REJECTED'], true)) {
            return back()->with('error', __('Only the person who uploaded a file can remove it, and only while the document is a draft.'));
        }

        Storage::disk('local')->delete($file->file_path);
        $file->delete();
        $audit->logCustom('Finance', class_basename($document), $document->id, 'DETACH', ['attachment_id' => $file->id, 'file_name' => $file->file_name]);

        return back()->with('success', __('File removed.'));
    }

    protected function document(string $documentType, int $id): Model
    {
        $model = self::DOCUMENTS[$documentType] ?? abort(404);

        return $model::findOrFail($id);
    }
}
