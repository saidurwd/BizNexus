<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('revalue_foreign_currency')->default(false)->after('is_postable');
        });

        Schema::create('fx_revaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->date('revaluation_date');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->restrictOnDelete();
            $table->foreignId('reversal_journal_id')->nullable()->constrained('journals')->restrictOnDelete();
            $table->decimal('net_gain_loss', 20, 4)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'revaluation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_revaluations');

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('revalue_foreign_currency');
        });
    }
};
