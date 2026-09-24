<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PermissionCatalog;

return new class extends Migration
{
    protected const PERMISSIONS = [
        'finance.fx-revaluation.view' => ['name' => 'View FX Revaluations', 'group' => 'Finance'],
        'finance.fx-revaluation.run' => ['name' => 'Run FX Revaluation', 'group' => 'Finance'],
    ];

    protected const LEGACY_EQUIVALENTS = [
        'finance.fx-revaluation.view' => ['finance.journals.view'],
        'finance.fx-revaluation.run' => ['finance.journals.post'],
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
