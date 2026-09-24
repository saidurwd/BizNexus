<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Company;

class RecurringJournal extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'frequency',
        'next_run_date',
        'lines',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lines' => 'array',
        'next_run_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
