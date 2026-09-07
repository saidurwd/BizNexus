<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringJournal extends Model
{
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
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
