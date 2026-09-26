<?php

namespace Modules\Core\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\NumberSequence;

class DocumentNumberService
{
    protected array $defaultDocumentTypes = [
        'JV' => ['prefix' => 'JV', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'PV' => ['prefix' => 'PV', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'RV' => ['prefix' => 'RV', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'BRV' => ['prefix' => 'BRV', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'BPV' => ['prefix' => 'BPV', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'SI' => ['prefix' => 'SI', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'CI' => ['prefix' => 'CI', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'CN' => ['prefix' => 'CN', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'SCN' => ['prefix' => 'SCN', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'SP' => ['prefix' => 'SP', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'CR' => ['prefix' => 'CR', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
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
                try {
                    NumberSequence::create([
                        'company_id' => $companyId,
                        'document_type' => $documentType,
                        'fiscal_year_id' => $fiscalYearId,
                        'prefix' => $this->defaultDocumentTypes[$documentType]['prefix'] ?? $documentType,
                        'format' => $this->defaultDocumentTypes[$documentType]['format'] ?? '{PREFIX}-{YEAR}-{SEQUENCE:6}',
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
     * Temporary reference for a document that has not been issued its official number yet.
     */
    public function draftReference(int $documentId): string
    {
        return sprintf('DRAFT-%06d', $documentId);
    }

    public function initializeDefaultsForCompany(int $companyId, ?int $fiscalYearId = null): void
    {
        foreach ($this->defaultDocumentTypes as $type => $config) {
            NumberSequence::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'document_type' => $type,
                    'fiscal_year_id' => $fiscalYearId,
                ],
                [
                    'prefix' => $config['prefix'],
                    'format' => $config['format'],
                    'last_number' => 0,
                    'is_active' => true,
                ]
            );
        }
    }

    public function resetSequence(int $companyId, string $documentType, ?int $fiscalYearId = null): void
    {
        NumberSequence::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->when($fiscalYearId, fn ($q) => $q->where('fiscal_year_id', $fiscalYearId))
            ->when(! $fiscalYearId, fn ($q) => $q->whereNull('fiscal_year_id'))
            ->update(['last_number' => 0]);
    }

    public function getCurrentNumber(int $companyId, string $documentType, ?int $fiscalYearId = null): int
    {
        $sequence = NumberSequence::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->when($fiscalYearId, fn ($q) => $q->where('fiscal_year_id', $fiscalYearId))
            ->when(! $fiscalYearId, fn ($q) => $q->whereNull('fiscal_year_id'))
            ->first();

        return $sequence?->last_number ?? 0;
    }

    public function previewNumber(int $companyId, string $documentType, ?int $fiscalYearId = null): string
    {
        $sequence = NumberSequence::firstOrCreate(
            [
                'company_id' => $companyId,
                'document_type' => $documentType,
                'fiscal_year_id' => $fiscalYearId,
            ],
            [
                'prefix' => $documentType,
                'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}',
                'last_number' => 0,
                'is_active' => true,
            ]
        );

        return $sequence->generateNumber();
    }
}
