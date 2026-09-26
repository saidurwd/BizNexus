<?php

namespace Modules\Sales;

use Illuminate\Support\ServiceProvider;
use Modules\Finance\Contracts\SalesCostOfGoods;
use Modules\Sales\Services\SalesOrderCostService;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalesCostOfGoods::class, SalesOrderCostService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
