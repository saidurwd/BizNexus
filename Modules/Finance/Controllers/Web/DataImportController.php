<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Services\Import\AccountImporter;
use Modules\Finance\Services\Import\CustomerImporter;
use Modules\Finance\Services\Import\Importer;
use Modules\Finance\Services\Import\OpeningBalanceImporter;
use Modules\Finance\Services\Import\SpreadsheetReader;
use Modules\Finance\Services\Import\SupplierImporter;
use Modules\Inventory\Services\Import\ProductImporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Spreadsheet imports: download a template, upload a CSV or XLSX file, review every row, then import it all in
 * one transaction. Nothing is written until the reviewed file is confirmed, and it is checked again then.
 */
class DataImportController extends Controller
{
    /**
     * @var array<string, array{importer: class-string<Importer>, permission: string, after: string}>
     */
    public const TYPES = [
        'accounts' => ['importer' => AccountImporter::class, 'permission' => 'finance.accounts.create', 'after' => 'finance.accounts.index'],
        'customers' => ['importer' => CustomerImporter::class, 'permission' => 'finance.customers.create', 'after' => 'finance.customers.index'],
        'suppliers' => ['importer' => SupplierImporter::class, 'permission' => 'finance.suppliers.create', 'after' => 'finance.suppliers.index'],
        'opening-balances' => ['importer' => OpeningBalanceImporter::class, 'permission' => 'finance.journals.create', 'after' => 'finance.journals.index'],
        'products' => ['importer' => ProductImporter::class, 'permission' => 'inventory.products.manage', 'after' => 'inventory.products.index'],
    ];

    public function index(): View
    {
        $types = collect(self::TYPES)->map(fn (array $type, string $key) => [
            'key' => $key,
            'permission' => $type['permission'],
            'importer' => app($type['importer']),
        ]);

        return view('finance.imports.index', compact('types'));
    }

    public function template(Request $request): StreamedResponse
    {
        $type = $request->route('type');
        $importer = $this->importer($type);

        return response()->streamDownload(function () use ($importer) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_keys($importer->columns()));

            foreach ($importer->example() as $row) {
                fputcsv($out, array_map(fn (string $column) => $row[$column] ?? '', array_keys($importer->columns())));
            }

            fclose($out);
        }, "{$type}-template.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $type = $request->route('type');
        $importer = $this->importer($type);
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xlsx'],
            'date' => [$type === 'opening-balances' ? 'required' : 'nullable', 'date'],
        ]);
        $extension = strtolower($validated['file']->getClientOriginalExtension()) === 'xlsx' ? 'xlsx' : 'csv';
        $token = Str::uuid()->toString().'.'.$extension;
        $validated['file']->storeAs($this->folder($request), $token, 'local');

        try {
            $result = $this->check($request, $importer, $token, ['date' => $validated['date'] ?? null]);
        } catch (InvalidAccountingTransactionException $exception) {
            Storage::disk('local')->delete($this->folder($request).'/'.$token);

            return back()->with('error', $exception->getMessage());
        }

        return view('finance.imports.preview', [
            'type' => $type,
            'importer' => $importer,
            'token' => $token,
            'options' => ['date' => $validated['date'] ?? null],
            'rows' => $result['rows'],
            'fileErrors' => $result['errors'],
            'invalidCount' => collect($result['rows'])->filter(fn (array $row) => $row['errors'] !== [])->count(),
            'actions' => collect($result['rows'])->countBy('action'),
        ]);
    }

    public function confirm(Request $request, AuditService $audit): RedirectResponse
    {
        $type = $request->route('type');
        $importer = $this->importer($type);
        $token = (string) $request->route('token');
        abort_unless(preg_match('/^[0-9a-f-]{36}\.(csv|xlsx)$/', $token) === 1, 404);
        abort_unless(Storage::disk('local')->exists($this->folder($request).'/'.$token), 404);
        $options = ['date' => $request->input('date')];

        $result = $this->check($request, $importer, $token, $options);

        if ($result['errors'] !== [] || collect($result['rows'])->contains(fn (array $row) => $row['errors'] !== [])) {
            return redirect()->route('finance.imports.index')->with('error', __('The file no longer passes the checks. Upload it again to see why.'));
        }

        $summary = DB::transaction(fn () => $importer->import($result['rows'], $options));
        Storage::disk('local')->delete($this->folder($request).'/'.$token);
        $audit->logCustom('Finance', 'DataImport', 0, 'IMPORT', ['type' => $type, 'rows' => count($result['rows']), 'summary' => $summary]);

        return redirect()->route(self::TYPES[$type]['after'])->with('success', $importer->label().': '.$summary);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{rows: list<array<string, mixed>>, errors: list<string>}
     */
    protected function check(Request $request, Importer $importer, string $token, array $options): array
    {
        $rows = app(SpreadsheetReader::class)->rows(Storage::disk('local')->path($this->folder($request).'/'.$token), pathinfo($token, PATHINFO_EXTENSION));

        if ($rows === []) {
            throw new InvalidAccountingTransactionException(__('The file has no data rows under the header row.'));
        }

        $missing = collect($importer->columns())->filter(fn (array $column) => $column[0])->keys()->diff(array_keys($rows[0]['data']));

        if ($missing->isNotEmpty()) {
            throw new InvalidAccountingTransactionException(__('Required columns are missing: :columns. Start from the template.', ['columns' => $missing->implode(', ')]));
        }

        return $importer->validate($rows, $options);
    }

    protected function importer(?string $type): Importer
    {
        return app(self::TYPES[$type]['importer'] ?? abort(404));
    }

    /**
     * Uploads are kept per company and user until confirmed.
     */
    protected function folder(Request $request): string
    {
        return 'imports/'.$this->getActiveCompanyId().'/'.$request->user()->id;
    }
}
