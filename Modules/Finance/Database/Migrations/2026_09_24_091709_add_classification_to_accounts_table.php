<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_control_account')->default(false)->after('revalue_foreign_currency');
            $table->string('cash_flow_category', 20)->nullable()->after('is_control_account');
            $table->boolean('is_current')->nullable()->after('cash_flow_category');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['is_control_account', 'cash_flow_category', 'is_current']);
        });
    }
};
