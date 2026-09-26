<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PermissionCatalog;

/**
 * Viewing activity logs, security events and login history follows the audit log permission; the System
 * screens (health, queues, scheduled jobs) start with super-admin only.
 */
return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::install();
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', [
            'core.activity-logs.view', 'core.security-events.view', 'core.login-history.view', 'core.system.view', 'core.system.manage',
        ])->delete();
    }
};
