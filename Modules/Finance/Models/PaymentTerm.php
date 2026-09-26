<?php

namespace Modules\Finance\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Services\CompanyContextService;

/**
 * When an invoice falls due: a number of days after the invoice date, or after the end of the invoice's month
 * ("30 days end of month"), with an optional discount for paying early.
 */
class PaymentTerm extends Model
{
    use BelongsToCompany;

    public const BASIS_INVOICE_DATE = 'invoice_date';

    public const BASIS_END_OF_MONTH = 'end_of_month';

    /**
     * @var list<array{code: string, name: string, due_days: int, due_basis: string}>
     */
    public const DEFAULTS = [
        ['code' => 'DUE', 'name' => 'Due on receipt', 'due_days' => 0, 'due_basis' => self::BASIS_INVOICE_DATE],
        ['code' => 'NET15', 'name' => 'Net 15 days', 'due_days' => 15, 'due_basis' => self::BASIS_INVOICE_DATE],
        ['code' => 'NET30', 'name' => 'Net 30 days', 'due_days' => 30, 'due_basis' => self::BASIS_INVOICE_DATE],
        ['code' => 'NET60', 'name' => 'Net 60 days', 'due_days' => 60, 'due_basis' => self::BASIS_INVOICE_DATE],
        ['code' => 'EOM30', 'name' => '30 days end of month', 'due_days' => 30, 'due_basis' => self::BASIS_END_OF_MONTH],
    ];

    protected $fillable = ['company_id', 'code', 'name', 'due_days', 'due_basis', 'discount_percent', 'discount_days', 'status'];

    protected $casts = [
        'due_days' => 'integer',
        'discount_percent' => 'decimal:2',
        'discount_days' => 'integer',
    ];

    /**
     * Give a company the usual payment terms, leaving any it already has.
     */
    public static function createDefaultsFor(int $companyId): void
    {
        app(CompanyContextService::class)->runAs($companyId, function () use ($companyId) {
            foreach (self::DEFAULTS as $term) {
                static::firstOrCreate(['company_id' => $companyId, 'code' => $term['code']], [...$term, 'status' => 'active']);
            }
        });
    }

    public function dueDate(CarbonInterface $invoiceDate): CarbonImmutable
    {
        return $this->startDate($invoiceDate)->addDays($this->due_days);
    }

    public function discountDate(CarbonInterface $invoiceDate): ?CarbonImmutable
    {
        return $this->discount_percent && $this->discount_days !== null
            ? $this->startDate($invoiceDate)->addDays($this->discount_days)
            : null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    protected function startDate(CarbonInterface $invoiceDate): CarbonImmutable
    {
        $date = CarbonImmutable::parse($invoiceDate)->startOfDay();

        return $this->due_basis === self::BASIS_END_OF_MONTH ? $date->endOfMonth()->startOfDay() : $date;
    }
}
