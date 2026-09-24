<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PermissionCatalog;

return new class extends Migration
{
    protected const PERMISSIONS = [
        'core.fiscal-years.close' => ['name' => 'Close Fiscal Years', 'group' => 'Administration'],
        'core.fiscal-years.reopen' => ['name' => 'Reopen Fiscal Years', 'group' => 'Administration'],
    ];

    protected const LEGACY_EQUIVALENTS = [
        'core.fiscal-years.close' => ['core.periods.close'],
        'core.fiscal-years.reopen' => ['core.periods.reopen'],
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
