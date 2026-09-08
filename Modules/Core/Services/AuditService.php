<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\AuditLog;

class AuditService
{
    public function log(
        string $module,
        string $entityType,
        ?int $entityId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create([
            'company_id' => $companyId ?? $this->getCompanyId(),
            'user_id' => $user?->id,
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logCreate(string $module, string $entityType, int $entityId, array $data, ?int $companyId = null): AuditLog
    {
        return $this->log($module, $entityType, $entityId, 'CREATE', null, $data, $companyId);
    }

    public function logUpdate(string $module, string $entityType, int $entityId, array $oldData, array $newData, ?int $companyId = null): AuditLog
    {
        return $this->log($module, $entityType, $entityId, 'UPDATE', $oldData, $newData, $companyId);
    }

    public function logDelete(string $module, string $entityType, int $entityId, array $data, ?int $companyId = null): AuditLog
    {
        return $this->log($module, $entityType, $entityId, 'DELETE', $data, null, $companyId);
    }

    public function logCustom(string $module, string $entityType, int $entityId, string $action, array $data, ?int $companyId = null): AuditLog
    {
        return $this->log($module, $entityType, $entityId, $action, null, $data, $companyId);
    }

    protected function getCompanyId(): ?int
    {
        return app(CompanyContextService::class)->getActiveCompanyId();
    }
}
