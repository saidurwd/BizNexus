<?php

namespace Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Events\SupplierInvoiceApproved;
use Modules\Finance\Events\CustomerInvoiceApproved;
use Modules\Finance\Events\PaymentApproved;
use Modules\Finance\Events\ReceiptApproved;

class ProcessIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $eventClass,
        public array $payload
    ) {}

    public function handle(): void
    {
        match ($this->eventClass) {
            SupplierInvoiceApproved::class => event(new SupplierInvoiceApproved($this->payload['invoice'])),
            CustomerInvoiceApproved::class => event(new CustomerInvoiceApproved($this->payload['invoice'])),
            PaymentApproved::class => event(new PaymentApproved($this->payload['payment'])),
            ReceiptApproved::class => event(new ReceiptApproved($this->payload['receipt'])),
            default => null,
        };
    }
}
