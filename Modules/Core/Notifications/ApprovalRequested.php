<?php

namespace Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells an approver that a document is waiting for them. Stored in the database so it shows in the
 * notification centre straight away, without a queue worker.
 */
class ApprovalRequested extends Notification
{
    use Queueable;

    public function __construct(
        public string $documentLabel,
        public string $documentNumber,
        public string $url,
        public ?string $submittedBy,
        public ?string $amount = null,
        public ?string $currency = null,
        public ?int $companyId = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'approval_requested',
            'message' => __(':document :number is waiting for your approval', ['document' => $this->documentLabel, 'number' => $this->documentNumber]),
            'document' => $this->documentLabel,
            'number' => $this->documentNumber,
            'url' => $this->url,
            'submitted_by' => $this->submittedBy,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'company_id' => $this->companyId,
        ];
    }
}
