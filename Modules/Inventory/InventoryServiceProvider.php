<?php

namespace Modules\Inventory;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Company;
use Modules\Inventory\Models\Unit;

class InventoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Company::created(fn (Company $company) => Unit::createDefaultsFor($company->id));
    }
}
