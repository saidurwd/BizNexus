<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('account_code', 50);
            $table->string('account_name', 255);
            $table->enum('account_type', ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE']);
            $table->string('account_category', 100)->nullable();
            $table->enum('normal_balance', ['DEBIT', 'CREDIT'])->default('DEBIT');
            $table->integer('level')->default(1);
            $table->boolean('is_group')->default(false);
            $table->boolean('is_postable')->default(true);
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('active');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['company_id', 'account_code']);
            $table->index('account_type');
            $table->index('status');
            $table->index(['company_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
