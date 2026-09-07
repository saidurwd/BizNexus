<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\Tax;
use Modules\Finance\Models\JournalLine;
use InvalidArgumentException;

class TaxService
{
    public function calculateTaxAmount(float $baseAmount, Tax $tax): array
    {
        if ($tax->is_inclusive) {
            $taxAmount = $baseAmount - ($baseAmount / (1 + $tax->rate / 100));
            $netAmount = $baseAmount / (1 + $tax->rate / 100);
        } else {
            $taxAmount = $baseAmount * ($tax->rate / 100);
            $netAmount = $baseAmount;
        }

        return [
            'tax_amount' => round($taxAmount, 4),
            'net_amount' => round($netAmount, 4),
            'gross_amount' => round($netAmount + $taxAmount, 4),
        ];
    }

    public function createTaxJournalLines(float $taxAmount, Tax $tax, int $companyId): array
    {
        if ($tax->tax_type === 'VAT') {
            return [
                [
                    'account_id' => $tax->input_account_id,
                    'debit' => $taxAmount,
                    'credit' => 0,
                    'description' => "Input VAT - {$tax->tax_name}",
                    'tax_id' => $tax->id,
                    'company_id' => $companyId,
                ],
            ];
        }

        if ($tax->tax_type === 'WITHHOLDING_TAX') {
            return [
                [
                    'account_id' => $tax->input_account_id,
                    'debit' => $taxAmount,
                    'credit' => 0,
                    'description' => "Withholding Tax - {$tax->tax_name}",
                    'tax_id' => $tax->id,
                    'company_id' => $companyId,
                ],
                [
                    'account_id' => $tax->output_account_id,
                    'debit' => 0,
                    'credit' => $taxAmount,
                    'description' => "Withholding Tax Payable - {$tax->tax_name}",
                    'tax_id' => $tax->id,
                    'company_id' => $companyId,
                ],
            ];
        }

        return [];
    }

    public function getTaxByCode(string $taxCode): ?Tax
    {
        return Tax::where('tax_code', $taxCode)
            ->where('status', 'active')
            ->first();
    }

    public function validateTaxRate(float $rate): void
    {
        if ($rate < 0 || $rate > 100) {
            throw new InvalidArgumentException('Tax rate must be between 0 and 100.');
        }
    }

    public function calculateWithholdingTax(float $amount, float $rate): float
    {
        return round($amount * ($rate / 100), 4);
    }
}
