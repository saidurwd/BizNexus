<?php

namespace Modules\Sales;

use Illuminate\Support\ServiceProvider;
use Modules\Finance\Contracts\SalesCostOfGoods;
use Modules\Inventory\Contracts\StockReservations;
use Modules\Sales\Services\SalesOrderCostService;
use Modules\Sales\Services\SalesOrderReservations;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalesCostOfGoods::class, SalesOrderCostService::class);
        $this->app->bind(StockReservations::class, SalesOrderReservations::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
