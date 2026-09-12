<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Finance\Events\CustomerInvoiceApproved;
use Modules\Finance\Events\JournalPosted;
use Modules\Finance\Events\PaymentApproved;
use Modules\Finance\Events\ReceiptApproved;
use Modules\Finance\Events\SupplierDebitNoteApproved;
use Modules\Finance\Events\SupplierInvoiceApproved;
use Modules\Finance\Listeners\CreatePaymentAccountingEntry;
use Modules\Finance\Listeners\CreatePurchaseInvoiceAccountingEntry;
use Modules\Finance\Listeners\CreateReceiptAccountingEntry;
use Modules\Finance\Listeners\CreateSalesInvoiceAccountingEntry;
use Modules\Finance\Listeners\ProcessIntegrationJob;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SupplierInvoiceApproved::class => [
            CreatePurchaseInvoiceAccountingEntry::class,
        ],
        PaymentApproved::class => [
            CreatePaymentAccountingEntry::class,
        ],
        ReceiptApproved::class => [
            CreateReceiptAccountingEntry::class,
        ],
        SupplierDebitNoteApproved::class => [
            ProcessIntegrationJob::class,
        ],
        CustomerInvoiceApproved::class => [
            CreateSalesInvoiceAccountingEntry::class,
        ],
        JournalPosted::class => [
            ProcessIntegrationJob::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
