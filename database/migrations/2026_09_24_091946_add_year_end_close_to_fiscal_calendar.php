<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->string('period_pattern', 20)->default('monthly')->after('end_date');
            $table->foreignId('closing_journal_id')->nullable()->after('is_current')->constrained('journals')->restrictOnDelete();
        });

        Schema::table('fiscal_periods', function (Blueprint $table) {
            $table->boolean('is_adjustment')->default(false)->after('period_number');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->boolean('adjustment_period')->default(false)->after('fiscal_period_id');
        });
    }

    public function down(): void
    {
        Schema::table('journals', fn (Blueprint $table) => $table->dropColumn('adjustment_period'));
        Schema::table('fiscal_periods', fn (Blueprint $table) => $table->dropColumn('is_adjustment'));
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closing_journal_id');
            $table->dropColumn('period_pattern');
        });
    }
};
