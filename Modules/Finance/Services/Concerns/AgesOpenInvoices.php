<?php

namespace Modules\Finance\Services\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\SupplierInvoice;

/**
 * Ages open invoices by days past their due date at the company's business date. Amounts are converted to the
 * functional currency at each invoice's rate so that invoices in different currencies can be added up.
 */
trait AgesOpenInvoices
{
    /**
     * @param  Collection<int, CustomerInvoice|SupplierInvoice>  $invoices
     * @return array{as_of_date: string, current: string, days_1_30: string, days_31_60: string, days_61_90: string, days_91_180: string, days_180_plus: string, total: string, invoices: list<array<string, mixed>>, parties: list<array{party_id: int, party_name: string, current: string, days_1_30: string, days_31_60: string, days_61_90: string, over_90_days: string, total: string}>}
     */
    protected function ageOpenInvoices(Collection $invoices, string $partyRelation, ?CarbonInterface $asOf = null): array
    {
        $asOf ??= app(CompanyContextService::class)->today();
        $zero = '0.0000';
        $aging = ['as_of_date' => $asOf->toDateString(), 'current' => $zero, 'days_1_30' => $zero, 'days_31_60' => $zero, 'days_61_90' => $zero, 'days_91_180' => $zero, 'days_180_plus' => $zero, 'total' => $zero, 'invoices' => [], 'parties' => []];
        $parties = [];

        foreach ($invoices as $invoice) {
            $daysPastDue = $invoice->getDaysOutstanding($asOf);
            $functional = bcmul((string) $invoice->outstanding_amount, (string) ($invoice->exchange_rate ?: 1), 4);
            $bucket = match (true) {
                $daysPastDue <= 0 => 'current',
                $daysPastDue <= 30 => 'days_1_30',
                $daysPastDue <= 60 => 'days_31_60',
                $daysPastDue <= 90 => 'days_61_90',
                $daysPastDue <= 180 => 'days_91_180',
                default => 'days_180_plus',
            };
            $partyBucket = in_array($bucket, ['days_91_180', 'days_180_plus'], true) ? 'over_90_days' : $bucket;
            $party = $invoice->{$partyRelation};

            $aging[$bucket] = bcadd($aging[$bucket], $functional, 4);
            $aging['total'] = bcadd($aging['total'], $functional, 4);

            $parties[$party->id] ??= ['party_id' => $party->id, 'party_name' => $party->name, 'current' => $zero, 'days_1_30' => $zero, 'days_31_60' => $zero, 'days_61_90' => $zero, 'over_90_days' => $zero, 'total' => $zero];
            $parties[$party->id][$partyBucket] = bcadd($parties[$party->id][$partyBucket], $functional, 4);
            $parties[$party->id]['total'] = bcadd($parties[$party->id]['total'], $functional, 4);

            $aging['invoices'][] = [
                'invoice_number' => $invoice->invoice_number,
                "{$partyRelation}_name" => $party->name,
                'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'currency' => $invoice->currency?->code,
                'total_amount' => $invoice->total_amount,
                'outstanding_amount' => $invoice->outstanding_amount,
                'outstanding_functional' => $functional,
                'days_outstanding' => $daysPastDue,
            ];
        }

        $aging['parties'] = collect($parties)->sortBy('party_name')->values()->all();

        return $aging;
    }
}
