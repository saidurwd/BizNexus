<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->string('rate_type', 20)->default('spot')->after('rate_date');
            $table->unique(['company_id', 'currency_id', 'rate_type', 'rate_date']);
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'currency_id', 'rate_type', 'rate_date']);
            $table->dropColumn('rate_type');
        });
    }
};
