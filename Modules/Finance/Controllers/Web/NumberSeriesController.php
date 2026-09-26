<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Models\NumberSequence;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Finance\Controllers\Controller;

/**
 * Document numbering per company. Prefix and format apply to the whole series; the next number can only move
 * forward so numbers are never issued twice and gaps are explicit.
 */
class NumberSeriesController extends Controller
{
    public const FORMAT_TOKENS = ['{PREFIX}', '{YEAR}', '{YY}', '{MONTH}', '{DAY}'];

    public function index(DocumentNumberService $numbers): View
    {
        $companyId = (int) $this->getActiveCompanyId();
        $today = app(CompanyContextService::class)->today();

        $series = collect(DocumentNumberService::TYPES)->map(function (array $type, string $code) use ($companyId, $numbers, $today) {
            $current = $this->currentSequence($companyId, $code);
            $settings = $numbers->settings($companyId, $code);
            $preview = new NumberSequence([...$settings, 'last_number' => ($current?->last_number ?? 0) + 1]);

            return [
                'code' => $code,
                'label' => $type['label'],
                'prefix' => $settings['prefix'],
                'format' => $settings['format'],
                'next_number' => ($current?->last_number ?? 0) + 1,
                'preview' => $preview->generateNumber($today),
                'per_fiscal_year' => $code === 'JV',
            ];
        });

        return view('finance.number-series.index', ['series' => $series, 'tokens' => self::FORMAT_TOKENS]);
    }

    public function update(Request $request, string $documentType, AuditService $audit): RedirectResponse
    {
        abort_unless(array_key_exists($documentType, DocumentNumberService::TYPES), 404);
        $companyId = (int) $this->getActiveCompanyId();
        $current = $this->currentSequence($companyId, $documentType);
        $minimumNext = ($current?->last_number ?? 0) + 1;

        $validated = $request->validate([
            'prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-_\/.]*$/'],
            'format' => ['required', 'string', 'max:60', 'regex:/\{SEQUENCE:([1-9]|1[0-2])\}/', function (string $attribute, string $format, $fail) {
                $rest = preg_replace('/\{SEQUENCE:\d+\}/', '', str_replace(self::FORMAT_TOKENS, '', $format));

                if (! preg_match('/^[A-Za-z0-9\-_\/.# ]*$/', $rest)) {
                    $fail(__('The format may contain only :tokens, {SEQUENCE:n}, letters, digits and - _ / . #', ['tokens' => implode(', ', self::FORMAT_TOKENS)]));
                }
            }],
            'next_number' => ['required', 'integer', 'min:'.$minimumNext],
        ], ['next_number.min' => __('Numbers up to :last have been issued; the next number cannot be lower than :next.', ['last' => $minimumNext - 1, 'next' => $minimumNext])]);

        DB::transaction(function () use ($companyId, $documentType, $validated, $current) {
            NumberSequence::where('company_id', $companyId)->where('document_type', $documentType)
                ->update(['prefix' => $validated['prefix'] ?? '', 'format' => $validated['format']]);

            $sequence = $current ?? NumberSequence::create([
                'company_id' => $companyId,
                'document_type' => $documentType,
                'fiscal_year_id' => $this->currentFiscalYearId($companyId, $documentType),
                'prefix' => $validated['prefix'] ?? '',
                'format' => $validated['format'],
                'last_number' => 0,
                'is_active' => true,
            ]);

            NumberSequence::whereKey($sequence->id)->lockForUpdate()->first()->update(['last_number' => (int) $validated['next_number'] - 1]);
        });

        $audit->logCustom('Finance', 'NumberSeries', 0, 'UPDATE', ['document_type' => $documentType, ...$validated], $companyId);

        return back()->with('success', __('Numbering for :type updated.', ['type' => DocumentNumberService::TYPES[$documentType]['label']]));
    }

    /**
     * The series in use now: this fiscal year's for journals, the company-wide one for everything else.
     */
    protected function currentSequence(int $companyId, string $documentType): ?NumberSequence
    {
        $fiscalYearId = $this->currentFiscalYearId($companyId, $documentType);

        return NumberSequence::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->when($fiscalYearId, fn ($query) => $query->where('fiscal_year_id', $fiscalYearId), fn ($query) => $query->whereNull('fiscal_year_id'))
            ->first();
    }

    protected function currentFiscalYearId(int $companyId, string $documentType): ?int
    {
        if ($documentType !== 'JV') {
            return null;
        }

        $today = app(CompanyContextService::class)->today()->toDateString();

        return FiscalYear::where('company_id', $companyId)->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->value('id');
    }
}
