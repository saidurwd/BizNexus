<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Core\Models\ActivityLog;
use Modules\Core\Services\CompanyContextService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs every change a signed-in user makes and every download of data, after the response has been sent.
 * Request bodies are never stored.
 */
class RecordUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('activity.started_at', microtime(true));

        $response = $next($request);

        // The company context belongs to the request; capture it before it is torn down.
        $request->attributes->set('activity.company_id', app(CompanyContextService::class)->getActiveCompanyId());

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $user = $request->user();

        if (! $user || ! $this->shouldRecord($request)) {
            return;
        }

        ActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'company_id' => $request->attributes->get('activity.company_id'),
            'user_id' => $user->id,
            'method' => $request->method(),
            'route_name' => $request->route()?->getName(),
            'path' => Str::limit('/'.ltrim($request->path(), '/'), 490, ''),
            'status' => $response->getStatusCode(),
            'duration_ms' => (int) round((microtime(true) - (float) $request->attributes->get('activity.started_at', microtime(true))) * 1000),
            'ip_address' => $request->ip(),
        ]);
    }

    protected function shouldRecord(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return true;
        }

        $routeName = (string) $request->route()?->getName();

        return Str::endsWith($routeName, config('security.logged_download_routes', []));
    }
}
