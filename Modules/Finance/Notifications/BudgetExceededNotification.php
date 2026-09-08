<?php

namespace Modules\Finance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BudgetExceededNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $budgetName,
        public float $budget,
        public float $actual,
        public float $variance
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Budget Exceeded: {$this->budgetName}")
            ->line("Budget has been exceeded: {$this->budgetName}")
            ->line("Budget: " . number_format($this->budget, 2))
            ->line("Actual: " . number_format($this->actual, 2))
            ->line("Variance: " . number_format($this->variance, 2))
            ->line('Please review and take necessary action.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'budget_exceeded',
            'budget_name' => $this->budgetName,
            'budget' => $this->budget,
            'actual' => $this->actual,
            'variance' => $this->variance,
            'message' => "Budget exceeded for {$this->budgetName}",
        ];
    }
}
