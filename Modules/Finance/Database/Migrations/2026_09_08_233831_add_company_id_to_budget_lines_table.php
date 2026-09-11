<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('budget_id');
            $table->index(['company_id']);
        });

        \DB::table('budget_lines')
            ->whereExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('budgets')
                    ->whereColumn('budgets.id', 'budget_lines.budget_id');
            })
            ->update(['company_id' => \DB::table('budgets')->select('company_id')->whereColumn('budgets.id', 'budget_lines.budget_id')]);

        Schema::table('budget_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
