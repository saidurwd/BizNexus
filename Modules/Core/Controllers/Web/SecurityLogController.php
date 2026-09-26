<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Core\Models\ActivityLog;
use Modules\Core\Models\LoginHistory;
use Modules\Core\Models\SecurityEvent;

/**
 * Activity logs, security events and login history of the signed-in user's tenant.
 */
class SecurityLogController extends Controller
{
    public function activity(Request $request): View
    {
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'method' => ['nullable', 'in:POST,PUT,PATCH,DELETE,GET'],
            'outcome' => ['nullable', 'in:success,error'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $activities = ActivityLog::visibleTo($request->user())
            ->with(['user:id,name,email', 'company:id,code'])
            ->when($filters['user_id'] ?? null, fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['method'] ?? null, fn (Builder $query, string $method) => $query->where('method', $method))
            ->when(($filters['outcome'] ?? null) === 'success', fn (Builder $query) => $query->where('status', '<', 400))
            ->when(($filters['outcome'] ?? null) === 'error', fn (Builder $query) => $query->where('status', '>=', 400))
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->where(fn (Builder $query) => $query
                ->where('path', 'like', '%'.addcslashes($term, '%_\\').'%')
                ->orWhere('route_name', 'like', '%'.addcslashes($term, '%_\\').'%')))
            ->tap(fn (Builder $query) => $this->dateRange($query, 'created_at', $filters))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('core.security.activity', ['activities' => $activities, 'filters' => $filters, 'users' => $this->tenantUsers($request->user())]);
    }

    public function events(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', 'string', 'max:50'],
            'severity' => ['nullable', 'in:info,warning,critical'],
            'user_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $visible = fn () => SecurityEvent::visibleTo($request->user());

        $events = $visible()
            ->with('user:id,name,email')
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['severity'] ?? null, fn (Builder $query, string $severity) => $query->where('severity', $severity))
            ->when($filters['user_id'] ?? null, fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->tap(fn (Builder $query) => $this->dateRange($query, 'created_at', $filters))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $summary = [
            'failed_logins_24h' => $visible()->where('type', SecurityEvent::LOGIN_FAILED)->where('created_at', '>=', now()->subDay())->count(),
            'lockouts_7d' => $visible()->where('type', SecurityEvent::LOCKOUT)->where('created_at', '>=', now()->subWeek())->count(),
            'access_denied_24h' => $visible()->where('type', SecurityEvent::ACCESS_DENIED)->where('created_at', '>=', now()->subDay())->count(),
            'two_factor_disabled_30d' => $visible()->where('type', SecurityEvent::TWO_FACTOR_DISABLED)->where('created_at', '>=', now()->subMonth())->count(),
        ];

        return view('core.security.events', [
            'events' => $events,
            'filters' => $filters,
            'summary' => $summary,
            'types' => $visible()->distinct()->orderBy('type')->pluck('type'),
            'users' => $this->tenantUsers($request->user()),
        ]);
    }

    public function logins(Request $request): View
    {
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logins = LoginHistory::visibleTo($request->user())
            ->with('user:id,name,email')
            ->when($filters['user_id'] ?? null, fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['active'] ?? false, fn (Builder $query) => $query->whereNull('logged_out_at')->where('logged_in_at', '>=', now()->subMinutes((int) config('session.lifetime'))))
            ->tap(fn (Builder $query) => $this->dateRange($query, 'logged_in_at', $filters))
            ->latest('logged_in_at')
            ->paginate(50)
            ->withQueryString();

        return view('core.security.logins', ['logins' => $logins, 'filters' => $filters, 'users' => $this->tenantUsers($request->user())]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function dateRange(Builder $query, string $column, array $filters): void
    {
        $query
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate($column, '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate($column, '<=', $to));
    }

    /**
     * @return Collection<int, User>
     */
    protected function tenantUsers(User $user)
    {
        return User::where('tenant_id', $user->tenant_id)->orderBy('name')->get(['id', 'name', 'email']);
    }
}
