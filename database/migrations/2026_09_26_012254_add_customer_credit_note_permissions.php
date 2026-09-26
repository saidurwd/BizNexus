<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PermissionCatalog;

/**
 * Customer credit note permissions, granted to every role holding the matching customer invoice permission.
 */
return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::install();
    }

    public function down(): void
    {
        DB::table('permissions')->where('slug', 'like', 'finance.customer-credit-notes.%')->delete();
    }
};
