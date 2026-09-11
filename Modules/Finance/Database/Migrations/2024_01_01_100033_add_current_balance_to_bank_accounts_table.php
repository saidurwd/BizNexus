<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_accounts', 'current_balance')) {
                $table->decimal('current_balance', 20, 4)->nullable()->after('opening_balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('current_balance');
        });
    }
};
