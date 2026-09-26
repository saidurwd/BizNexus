<?php

namespace Modules\Core;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Listeners\RecordScheduledTaskRuns;
use Modules\Core\Listeners\RecordSecurityActivity;
use Modules\Core\Services\BranchAccessService;
use Modules\Core\Services\CompanyAccessService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DepartmentAccessService;
use Modules\Core\Services\PermissionService;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CompanyContextService::class, function ($app) {
            return new CompanyContextService;
        });

        $this->app->singleton(CompanyAccessService::class, function ($app) {
            return new CompanyAccessService;
        });

        $this->app->singleton(BranchAccessService::class, function ($app) {
            return new BranchAccessService;
        });

        $this->app->singleton(DepartmentAccessService::class, function ($app) {
            return new DepartmentAccessService;
        });

        $this->app->scoped(PermissionService::class, function ($app) {
            return new PermissionService;
        });
    }

    public function boot(): void
    {
        Event::subscribe(RecordSecurityActivity::class);
        Event::subscribe(RecordScheduledTaskRuns::class);

        Gate::before(function ($user, string $ability) {
            if (str_contains($ability, '.') && app(PermissionService::class)->hasPermission($ability, $user->id)) {
                return true;
            }

            return null;
        });
    }
}
