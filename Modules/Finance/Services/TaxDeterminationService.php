<?php

namespace Modules\Finance\Services;

use Modules\Core\Models\Company;
use Modules\Core\Scopes\CompanyScope;
use Modules\Finance\Models\TaxRule;

/**
 * Chooses the tax code for a line from the company's rules: direction (sales/purchase), counterparty
 * country, counterparty type (B2B when it has a tax registration number) and supply type. The most
 * specific rule wins; ties go to the lowest priority number.
 */
class TaxDeterminationService
{
    public function determine(Company $company, string $direction, ?string $counterpartyCountry, bool $counterpartyIsBusiness, ?string $supplyType): ?TaxRule
    {
        $counterpartyCountry = $counterpartyCountry ? strtoupper($counterpartyCountry) : null;

        return TaxRule::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->id)
            ->where('direction', $direction)
            ->where(fn ($query) => $query->whereNull('counterparty_country')->orWhere('counterparty_country', $counterpartyCountry))
            ->whereIn('counterparty_type', ['any', $counterpartyIsBusiness ? 'b2b' : 'b2c'])
            ->whereIn('supply_type', array_filter(['any', $supplyType]))
            ->get()
            ->sortBy(fn (TaxRule $rule) => [
                -(($rule->counterparty_country ? 4 : 0) + ($rule->counterparty_type !== 'any' ? 2 : 0) + ($rule->supply_type !== 'any' ? 1 : 0)),
                $rule->priority,
            ])
            ->first();
    }
}
