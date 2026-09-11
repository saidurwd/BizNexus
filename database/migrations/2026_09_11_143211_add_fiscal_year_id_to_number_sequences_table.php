<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('number_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('fiscal_year_id')->nullable()->after('company_id');
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->nullOnDelete();
            $table->unique(['company_id', 'document_type', 'fiscal_year_id']);
        });
    }

    public function down(): void
    {
        Schema::table('number_sequences', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'document_type', 'fiscal_year_id']);
            $table->dropForeign(['fiscal_year_id']);
            $table->dropColumn('fiscal_year_id');
        });
    }
};
