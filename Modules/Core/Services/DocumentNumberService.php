<?php

namespace Modules\Core\Services;

use Modules\Core\Models\NumberSequence;
use Illuminate\Support\Str;

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
        'SP' => ['prefix' => 'SP', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
        'CR' => ['prefix' => 'CR', 'format' => '{PREFIX}-{YEAR}-{SEQUENCE:6}'],
    ];

    public function generateNumber(int $companyId, string $documentType, ?int $fiscalYearId = null): string
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

        return $sequence->getNextNumber();
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
            ->when($fiscalYearId, fn($q) => $q->where('fiscal_year_id', $fiscalYearId))
            ->when(!$fiscalYearId, fn($q) => $q->whereNull('fiscal_year_id'))
            ->update(['last_number' => 0]);
    }

    public function getCurrentNumber(int $companyId, string $documentType, ?int $fiscalYearId = null): int
    {
        $sequence = NumberSequence::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->when($fiscalYearId, fn($q) => $q->where('fiscal_year_id', $fiscalYearId))
            ->when(!$fiscalYearId, fn($q) => $q->whereNull('fiscal_year_id'))
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
