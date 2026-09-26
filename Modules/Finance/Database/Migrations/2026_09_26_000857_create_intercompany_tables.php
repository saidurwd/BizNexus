<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreignId('counterparty_company_id')->nullable()->after('company_id')->constrained('companies')->restrictOnDelete();
        });

        Schema::create('intercompany_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('target_company_id')->constrained('companies')->restrictOnDelete();
            $table->date('transaction_date');
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 20, 4);
            $table->string('description');
            $table->foreignId('source_journal_id')->constrained('journals')->restrictOnDelete();
            $table->foreignId('target_journal_id')->constrained('journals')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intercompany_transactions');

        Schema::table('journal_lines', fn (Blueprint $table) => $table->dropConstrainedForeignId('counterparty_company_id'));
    }
};
