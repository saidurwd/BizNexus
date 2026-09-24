<?php

namespace Modules\Finance\Services;

use InvalidArgumentException;
use Modules\Finance\Models\Tax;

class TaxService
{
    public function calculateTaxAmount(float $baseAmount, Tax $tax): array
    {
        $taxAmount = (string) $tax->calculateTax($baseAmount);
        $netAmount = $tax->is_inclusive
            ? bcsub((string) $baseAmount, $taxAmount, 4)
            : (string) $baseAmount;

        return [
            'tax_amount' => (float) $taxAmount,
            'net_amount' => (float) $netAmount,
            'gross_amount' => (float) bcadd($netAmount, $taxAmount, 4),
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
