<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyUserRole extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'role_id',
        'status',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Assignments in force today: active status and, when set, within their validity window.
     */
    public function scopeActive($query)
    {
        $today = now()->toDateString();

        return $query->where($this->qualifyColumn('status'), 'active')
            ->where(fn ($query) => $query->whereNull($this->qualifyColumn('valid_from'))->orWhere($this->qualifyColumn('valid_from'), '<=', $today))
            ->where(fn ($query) => $query->whereNull($this->qualifyColumn('valid_until'))->orWhere($this->qualifyColumn('valid_until'), '>=', $today));
    }
}
