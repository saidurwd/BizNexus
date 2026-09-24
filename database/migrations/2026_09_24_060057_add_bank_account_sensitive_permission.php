<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PermissionCatalog;

return new class extends Migration
{
    protected const PERMISSIONS = [
        'finance.bank-accounts.view-sensitive' => ['name' => 'View Full Bank Account Numbers', 'group' => 'Finance'],
    ];

    protected const LEGACY_EQUIVALENTS = [
        'finance.bank-accounts.view-sensitive' => ['finance.bank-accounts.update'],
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
