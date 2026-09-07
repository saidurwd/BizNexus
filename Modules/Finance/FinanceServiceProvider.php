<?php

namespace Modules\Finance;

use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Finance\Services\JournalService::class);
        $this->app->singleton(\Modules\Finance\Services\LedgerService::class);
        $this->app->singleton(\Modules\Finance\Services\ChartOfAccountsService::class);
        $this->app->singleton(\Modules\Finance\Services\SupplierInvoiceService::class);
        $this->app->singleton(\Modules\Finance\Services\CustomerInvoiceService::class);
        $this->app->singleton(\Modules\Finance\Services\PaymentService::class);
        $this->app->singleton(\Modules\Finance\Services\ReceiptService::class);
        $this->app->singleton(\Modules\Finance\Services\FinancialReportService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }
}
