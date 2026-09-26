<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\VisibleToTenant;

/**
 * One sign-in to the web application, closed when the user signs out.
 */
class LoginHistory extends Model
{
    use MassPrunable, VisibleToTenant;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'user_id', 'method', 'ip_address', 'user_agent', 'logged_in_at', 'logged_out_at'];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        return static::where('logged_in_at', '<', now()->subDays(config('security.retention_days.login_history')));
    }
}
