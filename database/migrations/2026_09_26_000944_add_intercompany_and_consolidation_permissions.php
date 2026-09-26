<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PermissionCatalog;

return new class extends Migration
{
    protected const PERMISSIONS = [
        'finance.intercompany.view' => ['name' => 'View Intercompany Transactions', 'group' => 'Finance'],
        'finance.intercompany.create' => ['name' => 'Create Intercompany Transactions', 'group' => 'Finance'],
        'finance.consolidation.view' => ['name' => 'View Consolidated Reports', 'group' => 'Finance'],
        'finance.consolidation.manage' => ['name' => 'Manage Consolidation Groups', 'group' => 'Finance'],
    ];

    protected const LEGACY_EQUIVALENTS = [
        'finance.intercompany.view' => ['finance.journals.view'],
        'finance.intercompany.create' => ['finance.journals.post'],
    ];

    public function up(): void
    {
        PermissionCatalog::install(self::PERMISSIONS, self::LEGACY_EQUIVALENTS);
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', array_keys(self::PERMISSIONS))->delete();
    }
};
