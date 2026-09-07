<?php

namespace Modules\Finance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PeriodClosingNotification extends Notification
{
    use Queueable;

    public function __construct(public string $periodName, public string $action = 'closing') {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = match ($this->action) {
            'closing' => "Fiscal Period Closing Reminder: {$this->periodName}",
            'closed' => "Fiscal Period Closed: {$this->periodName}",
            'reopened' => "Fiscal Period Reopened: {$this->periodName}",
        };

        return (new MailMessage)
            ->subject($subject)
            ->line("Fiscal period '{$this->periodName}' has been {$this->action}.")
            ->line('Please take appropriate action if needed.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'period_' . $this->action,
            'period_name' => $this->periodName,
            'action' => $this->action,
            'message' => "Fiscal period '{$this->periodName}' {$this->action}",
        ];
    }
}
