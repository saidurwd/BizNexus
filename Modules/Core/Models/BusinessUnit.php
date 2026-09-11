<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessUnit extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\BusinessUnitFactory::new();
    }
    protected $fillable = [
        'company_id',
        'division_id',
        'code',
        'name',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
