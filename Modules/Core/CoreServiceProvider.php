<?php

namespace Modules\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Core\Services\CompanyContextService::class);
        $this->app->singleton(\Modules\Core\Services\AccountingPeriodService::class);
        $this->app->singleton(\Modules\Core\Services\DocumentNumberService::class);
        $this->app->singleton(\Modules\Core\Services\AuditService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }
}
