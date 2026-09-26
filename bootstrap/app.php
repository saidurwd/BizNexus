<?php

use App\Providers\EventServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use JeroenNoten\LaravelAdminLte\AdminLteServiceProvider;
use Modules\Core\CoreServiceProvider;
use Modules\Core\Http\Middleware\BranchAccess;
use Modules\Core\Http\Middleware\CompanyAccess;
use Modules\Core\Http\Middleware\DepartmentAccess;
use Modules\Core\Http\Middleware\EnsureCompanyAndBranchSelected;
use Modules\Core\Http\Middleware\EnsureTwoFactorEnrolment;
use Modules\Core\Http\Middleware\Permission;
use Modules\Core\Http\Middleware\RecordUserActivity;
use Modules\Core\Http\Middleware\SetCompanyContext;
use Modules\Core\Http\Middleware\SetLocale;
use Modules\Core\Http\Middleware\SetTokenCompanyContext;
use Modules\Core\Http\Middleware\ShareAdminLte;
use Modules\Finance\FinanceServiceProvider;
use Modules\Inventory\InventoryServiceProvider;
use Modules\Workflow\WorkflowServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        CoreServiceProvider::class,
        FinanceServiceProvider::class,
        WorkflowServiceProvider::class,
        InventoryServiceProvider::class,
        EventServiceProvider::class,
        AdminLteServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web([
            SetCompanyContext::class,
            EnsureTwoFactorEnrolment::class,
            SetLocale::class,
            ShareAdminLte::class,
            RecordUserActivity::class,
        ]);

        $middleware->alias([
            'company.context' => SetCompanyContext::class,
            'company.access' => CompanyAccess::class,
            'branch.access' => BranchAccess::class,
            'department.access' => DepartmentAccess::class,
            'permission' => Permission::class,
            'company.and.branch' => EnsureCompanyAndBranchSelected::class,
            'api.company' => SetTokenCompanyContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
