<?php

namespace Modules\Finance;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Company;
use Modules\Finance\Contracts\PurchaseMatching;
use Modules\Finance\Contracts\SalesCostOfGoods;
use Modules\Finance\Contracts\TaxCalculator;
use Modules\Finance\Models\PaymentTerm;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Finance\Services\CustomerInvoiceService;
use Modules\Finance\Services\FinancialReportService;
use Modules\Finance\Services\JournalService;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\ReceiptService;
use Modules\Finance\Services\SupplierInvoiceService;
use Modules\Finance\Support\DirectPurchaseCost;
use Modules\Finance\Support\NoSalesCostOfGoods;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxCalculator::class, fn ($app) => $app->make(config('finance.tax.calculator')));
        $this->app->bindIf(PurchaseMatching::class, DirectPurchaseCost::class);
        $this->app->bindIf(SalesCostOfGoods::class, NoSalesCostOfGoods::class);

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

        Company::created(fn (Company $company) => PaymentTerm::createDefaultsFor($company->id));
    }
}
