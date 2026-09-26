<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PermissionCatalog;

return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::install();
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', ['inventory.products.view', 'inventory.products.manage', 'inventory.setup.view', 'inventory.setup.manage'])->delete();
    }
};
