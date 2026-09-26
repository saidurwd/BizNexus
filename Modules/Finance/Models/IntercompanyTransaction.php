<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;

/**
 * A charge between two companies of the same tenant, booked in both ledgers at once.
 */
class IntercompanyTransaction extends Model
{
    protected $fillable = [
        'tenant_id', 'source_company_id', 'target_company_id', 'transaction_date', 'currency_id', 'amount',
        'description', 'source_journal_id', 'target_journal_id', 'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:4',
    ];

    public function sourceCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'source_company_id');
    }

    public function targetCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'target_company_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
