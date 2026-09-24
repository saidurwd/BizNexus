<?php

namespace Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Events\CustomerInvoiceApproved;
use Modules\Finance\Events\PaymentApproved;
use Modules\Finance\Events\ReceiptApproved;
use Modules\Finance\Events\SupplierInvoiceApproved;

class ProcessIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $eventClass,
        public array $payload
    ) {}

    public function handle(CompanyContextService $companyContext): void
    {
        $companyId = collect($this->payload)->first(fn ($value) => $value instanceof Model)?->company_id;

        if ($companyId === null) {
            throw new InvalidArgumentException("Integration payload for {$this->eventClass} has no company-owned model.");
        }

        $companyContext->runAs($companyId, fn () => match ($this->eventClass) {
            SupplierInvoiceApproved::class => event(new SupplierInvoiceApproved($this->payload['invoice'])),
            CustomerInvoiceApproved::class => event(new CustomerInvoiceApproved($this->payload['invoice'])),
            PaymentApproved::class => event(new PaymentApproved($this->payload['payment'])),
            ReceiptApproved::class => event(new ReceiptApproved($this->payload['receipt'])),
            default => null,
        });
    }
}
