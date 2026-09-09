<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \Modules\Core\CoreServiceProvider::class,
        \Modules\Finance\FinanceServiceProvider::class,
        \JeroenNoten\LaravelAdminLte\AdminLteServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )



    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web([
            \Modules\Core\Http\Middleware\SetCompanyContext::class,
            \Modules\Core\Http\Middleware\ShareAdminLte::class,
        ]);
        
        $middleware->alias([
            'company.context' => \Modules\Core\Http\Middleware\SetCompanyContext::class,
            'company.access' => \Modules\Core\Http\Middleware\CompanyAccess::class,
            'branch.access' => \Modules\Core\Http\Middleware\BranchAccess::class,
            'department.access' => \Modules\Core\Http\Middleware\DepartmentAccess::class,
            'permission' => \Modules\Core\Http\Middleware\Permission::class,
            'company.and.branch' => \Modules\Core\Http\Middleware\EnsureCompanyAndBranchSelected::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
