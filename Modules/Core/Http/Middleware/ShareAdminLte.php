<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JeroenNoten\LaravelAdminLte\AdminLte;

class ShareAdminLte
{
    public function handle(Request $request, Closure $next)
    {
        view()->share('adminlte', app(AdminLte::class));

        return $next($request);
    }
}
