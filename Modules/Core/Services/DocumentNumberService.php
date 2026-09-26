<?php

namespace Modules\Core\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\NumberSequence;

/**
 * Gapless document numbers per company (journals restart each fiscal year). Each company may change a
 * series' prefix and format; a new fiscal year's series follows the company's latest settings.
 */
class DocumentNumberService
{
    public const DEFAULT_FORMAT = '{PREFIX}-{YEAR}-{SEQUENCE:6}';

    /**
     * Document types that are numbered, with their default prefix.
     *
     * @var array<string, array{label: string, prefix: string}>
     */
    public const TYPES = [
        'JV' => ['label' => 'Journals', 'prefix' => 'JV'],
        'CI' => ['label' => 'Customer invoices', 'prefix' => 'CI'],
        'CN' => ['label' => 'Customer credit notes', 'prefix' => 'CN'],
        'RV' => ['label' => 'Customer receipts', 'prefix' => 'RV'],
        'SI' => ['label' => 'Supplier invoices (when the supplier gives no number)', 'prefix' => 'SI'],
        'SCN' => ['label' => 'Supplier credit notes', 'prefix' => 'SCN'],
        'PV' => ['label' => 'Supplier payments', 'prefix' => 'PV'],
    ];

    /**
     * Issue the next number of the sequence. The sequence row is locked for the rest of the surrounding
     * transaction, so concurrent callers are serialised and a rolled-back caller does not consume a number.
     */
    public function generateNumber(int $companyId, string $documentType, ?int $fiscalYearId = null, ?CarbonInterface $documentDate = null): string
    {
        return DB::transaction(function () use ($companyId, $documentType, $fiscalYearId, $documentDate) {
            $sequenceQuery = fn () => NumberSequence::where('company_id', $companyId)
                ->where('document_type', $documentType)
                ->when($fiscalYearId, fn ($query) => $query->where('fiscal_year_id', $fiscalYearId), fn ($query) => $query->whereNull('fiscal_year_id'));

            if (! $sequenceQuery()->exists()) {
                $settings = $this->settings($companyId, $documentType);

                try {
                    NumberSequence::create([
                        'company_id' => $companyId,
                        'document_type' => $documentType,
                        'fiscal_year_id' => $fiscalYearId,
                        'prefix' => $settings['prefix'],
                        'format' => $settings['format'],
                        'last_number' => 0,
                        'is_active' => true,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Another request created the sequence first; lock and use that one.
                }
            }

            return $sequenceQuery()->lockForUpdate()->firstOrFail()->getNextNumber($documentDate);
        });
    }

    /**
     * The prefix and format a company uses for a document type: its latest series, or the defaults.
     *
     * @return array{prefix: string, format: string}
     */
    public function settings(int $companyId, string $documentType): array
    {
        $latest = NumberSequence::where('company_id', $companyId)->where('document_type', $documentType)->latest('id')->first();

        return [
            'prefix' => $latest?->prefix ?? self::TYPES[$documentType]['prefix'] ?? $documentType,
            'format' => $latest?->format ?? self::DEFAULT_FORMAT,
        ];
    }

    /**
     * Temporary reference for a document that has not been issued its official number yet.
     */
    public function draftReference(int $documentId): string
    {
        return sprintf('DRAFT-%06d', $documentId);
    }
}
