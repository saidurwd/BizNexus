<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\VisibleToTenant;

/**
 * Something that matters for account security: failed sign-ins, lockouts, two-factor and password changes,
 * and refused access.
 */
class SecurityEvent extends Model
{
    use MassPrunable, VisibleToTenant;

    public const LOGIN_FAILED = 'login_failed';

    public const LOCKOUT = 'lockout';

    public const TWO_FACTOR_FAILED = 'two_factor_failed';

    public const TWO_FACTOR_ENABLED = 'two_factor_enabled';

    public const TWO_FACTOR_DISABLED = 'two_factor_disabled';

    public const RECOVERY_CODES_REGENERATED = 'recovery_codes_regenerated';

    public const PASSWORD_CHANGED = 'password_changed';

    public const PASSWORD_RESET = 'password_reset';

    public const ACCESS_DENIED = 'access_denied';

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    public const UPDATED_AT = null;

    protected $fillable = ['tenant_id', 'user_id', 'email', 'type', 'severity', 'ip_address', 'user_agent', 'context'];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(config('security.retention_days.security_events')));
    }
}
