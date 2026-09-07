<?php

namespace Modules\Finance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\CustomerInvoice;

class InvoiceApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public $invoice,
        public string $type = 'supplier'
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $invoice = $this->invoice;
        $subject = match ($this->type) {
            'supplier' => "Supplier Invoice #{$invoice->invoice_number} Awaiting Approval",
            'customer' => "Customer Invoice #{$invoice->invoice_number} Awaiting Approval",
        };

        return (new MailMessage)
            ->subject($subject)
            ->line("Invoice #{$invoice->invoice_number} requires your approval.")
            ->line("Amount: " . number_format($invoice->total_amount, 2))
            ->line("Due Date: " . ($invoice->due_date ?? 'N/A'))
            ->action('Review Invoice', route('finance.supplier-invoices.show', $invoice->id))
            ->line('Please review and approve this invoice as soon as possible.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'invoice_approval',
            'invoice_type' => $this->type,
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
            'message' => "Invoice #{$this->invoice->invoice_number} requires approval",
        ];
    }
}
