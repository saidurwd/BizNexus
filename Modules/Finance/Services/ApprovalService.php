<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Services\AuditService;
use Modules\Finance\Exceptions\InvalidApprovalTransitionException;

class ApprovalService
{
    protected array $workflows = [
        'journal' => [
            'DRAFT' => ['SUBMITTED'],
            'SUBMITTED' => ['APPROVED', 'REJECTED'],
            'APPROVED' => ['POSTED'],
            'REJECTED' => ['DRAFT'],
            'POSTED' => ['REVERSED'],
            'CANCELLED' => [],
            'REVERSED' => [],
        ],
        'supplier_invoice' => [
            'draft' => ['submitted'],
            'submitted' => ['approved', 'rejected'],
            'approved' => ['paid', 'cancelled'],
            'rejected' => ['draft'],
            'cancelled' => [],
        ],
        'customer_invoice' => [
            'draft' => ['submitted'],
            'submitted' => ['approved', 'rejected'],
            'approved' => ['posted', 'cancelled'],
            'rejected' => ['draft'],
            'cancelled' => [],
        ],
    ];

    protected array $approvalLimits = [
        'MANAGER' => 50000,
        'DEPARTMENT_HEAD' => 500000,
        'CFO' => null,
    ];

    public function __construct(
        protected AuditService $audit
    ) {}

    public function canTransition(string $entityType, string $currentStatus, string $newStatus): bool
    {
        $workflow = $this->getWorkflow($entityType);
        
        return in_array($newStatus, $workflow[$currentStatus] ?? []);
    }

    public function getNextStatuses(string $entityType, string $currentStatus): array
    {
        $workflow = $this->getWorkflow($entityType);
        
        return $workflow[$currentStatus] ?? [];
    }

    public function getRequiredApproverRole(string $entityType, float $amount): ?string
    {
        if ($amount <= $this->approvalLimits['MANAGER']) {
            return 'MANAGER';
        }

        if ($amount <= $this->approvalLimits['DEPARTMENT_HEAD']) {
            return 'DEPARTMENT_HEAD';
        }

        return 'CFO';
    }

    public function transition(string $entityType, object $entity, string $newStatus, ?string $reason = null): void
    {
        $currentStatus = $entity->status ?? $entity->getAttribute('status');
        
        if (!$this->canTransition($entityType, $currentStatus, $newStatus)) {
            throw new InvalidApprovalTransitionException($entityType, $currentStatus, $newStatus);
        }

        $entity->status = $newStatus;
        $entity->save();

        $this->audit->logCustom(
            'Finance',
            ucfirst($entityType),
            $entity->id,
            strtoupper($newStatus),
            ['reason' => $reason, 'previous_status' => $currentStatus]
        );
    }

    protected function getWorkflow(string $entityType): array
    {
        return $this->workflows[$entityType] ?? [];
    }
}
