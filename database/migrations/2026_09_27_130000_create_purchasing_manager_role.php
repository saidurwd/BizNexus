<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Support\PurchasingManagerRole;

return new class extends Migration
{
    public function up(): void
    {
        PurchasingManagerRole::install();
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', PurchasingManagerRole::SLUG)->value('id');

        if ($roleId && ! DB::table('company_user_roles')->where('role_id', $roleId)->exists()) {
            DB::table('permission_role')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
