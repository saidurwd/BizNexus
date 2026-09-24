<?php

namespace Modules\Finance;

use Illuminate\Support\ServiceProvider;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\CustomerInvoiceService;
use Modules\Finance\Services\FinancialReportService;
use Modules\Finance\Services\JournalService;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\ReceiptService;
use Modules\Finance\Services\SupplierInvoiceService;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(JournalService::class);
        $this->app->scoped(LedgerService::class);
        $this->app->scoped(ChartOfAccountsService::class);
        $this->app->scoped(SupplierInvoiceService::class);
        $this->app->scoped(CustomerInvoiceService::class);
        $this->app->scoped(PaymentService::class);
        $this->app->scoped(ReceiptService::class);
        $this->app->scoped(FinancialReportService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views/finance', 'finance');
    }
}
