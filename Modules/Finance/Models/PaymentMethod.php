<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'description',
        'requires_reference',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requires_reference' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function requiresReference(): bool
    {
        return $this->requires_reference;
    }
}
