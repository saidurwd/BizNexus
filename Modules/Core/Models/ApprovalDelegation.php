<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;

/**
 * Temporarily lends a user's approval permissions in one company to another user.
 */
class ApprovalDelegation extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'delegator_id',
        'delegate_id',
        'starts_on',
        'ends_on',
        'reason',
        'revoked_at',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'revoked_at' => 'datetime',
    ];

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    /**
     * Delegations in force today.
     */
    public function scopeInForce(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->whereNull('revoked_at')->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today);
    }
}
