<?php

namespace Modules\Finance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Finance\Models\SupplierPayment;

class PaymentApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public SupplierPayment $payment) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment #{$this->payment->payment_number} Approved")
            ->line("Payment #{$this->payment->payment_number} has been approved.")
            ->line("Amount: " . number_format($this->payment->amount, 2))
            ->line("Supplier: " . ($this->payment->supplier?->name ?? 'N/A'))
            ->action('View Payment', route('finance.payments.show', $this->payment->id));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'payment_approved',
            'payment_id' => $this->payment->id,
            'payment_number' => $this->payment->payment_number,
            'amount' => $this->payment->amount,
            'message' => "Payment #{$this->payment->payment_number} approved",
        ];
    }
}
