<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('business_unit_id')->nullable()->after('branch_id');
            $table->foreign('business_unit_id')->references('id')->on('business_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
        });
    }
};
