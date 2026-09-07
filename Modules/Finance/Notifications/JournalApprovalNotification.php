<?php

namespace Modules\Finance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Finance\Models\Journal;

class JournalApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(public Journal $journal) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Journal #{$this->journal->journal_number} Awaiting Approval")
            ->line("Journal #{$this->journal->journal_number} requires your approval.")
            ->line("Date: " . $this->journal->journal_date->format('Y-m-d'))
            ->line("Amount: " . number_format($this->journal->total_debit, 2))
            ->action('Review Journal', route('finance.journals.show', $this->journal->id))
            ->line('Please review and approve this journal as soon as possible.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'journal_approval',
            'journal_id' => $this->journal->id,
            'journal_number' => $this->journal->journal_number,
            'amount' => $this->journal->total_debit,
            'message' => "Journal #{$this->journal->journal_number} requires approval",
        ];
    }
}
