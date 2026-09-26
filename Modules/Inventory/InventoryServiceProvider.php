<?php

namespace Modules\Inventory;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Company;
use Modules\Finance\Contracts\PurchaseMatching;
use Modules\Finance\Contracts\SalesCostOfGoods;
use Modules\Inventory\Models\Unit;
use Modules\Inventory\Services\PurchaseInvoiceMatcher;
use Modules\Inventory\Services\SalesCostService;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PurchaseMatching::class, PurchaseInvoiceMatcher::class);
        $this->app->bind(SalesCostOfGoods::class, SalesCostService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Company::created(fn (Company $company) => Unit::createDefaultsFor($company->id));
    }
}
