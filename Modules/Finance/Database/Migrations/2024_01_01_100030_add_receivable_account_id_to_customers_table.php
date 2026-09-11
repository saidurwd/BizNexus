<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'receivable_account_id')) {
                $table->unsignedBigInteger('receivable_account_id')->nullable()->after('currency_id');
                $table->foreign('receivable_account_id')->references('id')->on('accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['receivable_account_id']);
            $table->dropColumn('receivable_account_id');
        });
    }
};
