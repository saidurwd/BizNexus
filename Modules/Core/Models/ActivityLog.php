<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\VisibleToTenant;

/**
 * A change a user made through the application, or a download of data. Data-level changes (before and after)
 * are in the tamper-evident audit log; this records who did what, where, and how it went.
 */
class ActivityLog extends Model
{
    use MassPrunable, VisibleToTenant;

    public const UPDATED_AT = null;

    protected $fillable = ['tenant_id', 'company_id', 'user_id', 'method', 'route_name', 'path', 'status', 'duration_ms', 'ip_address'];

    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(config('security.retention_days.activity_logs')));
    }
}
