<?php

namespace Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Notifications\InvoiceApprovalNotification;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\CustomerInvoice;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $type,
        public int $entityId,
        public array $data = []
    ) {}

    public function handle(): void
    {
        match ($this->type) {
            'supplier_invoice_approval' => $this->sendSupplierInvoiceNotification(),
            'customer_invoice_approval' => $this->sendCustomerInvoiceNotification(),
            'payment_reminder' => $this->sendPaymentReminder(),
            'period_closing' => $this->sendPeriodClosingNotification(),
            default => null,
        };
    }

    protected function sendSupplierInvoiceNotification(): void
    {
        $invoice = SupplierInvoice::findOrFail($this->entityId);
        $approvers = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', ['Finance Manager', 'CFO']))->get();

        foreach ($approvers as $approver) {
            $approver->notify(new InvoiceApprovalNotification($invoice, 'supplier'));
        }
    }

    protected function sendCustomerInvoiceNotification(): void
    {
        $invoice = CustomerInvoice::findOrFail($this->entityId);
        $approvers = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', ['Finance Manager', 'CFO']))->get();

        foreach ($approvers as $approver) {
            $approver->notify(new InvoiceApprovalNotification($invoice, 'customer'));
        }
    }

    protected function sendPaymentReminder(): void
    {
        // Implementation for payment due reminders
    }

    protected function sendPeriodClosingNotification(): void
    {
        // Implementation for period closing reminders
    }
}
