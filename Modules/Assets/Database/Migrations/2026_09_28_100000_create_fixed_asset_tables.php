<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->foreignId('asset_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('depreciation_expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('disposal_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('depreciation_method', 20)->default('straight_line');
            $table->unsignedSmallInteger('useful_life_months')->default(60);
            $table->decimal('declining_rate', 7, 4)->nullable();
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('asset_number', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('location')->nullable();
            $table->string('custodian')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('tag', 100)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('acquisition_date');
            $table->date('in_service_date');
            $table->decimal('cost', 20, 4);
            $table->decimal('residual_value', 20, 4)->default(0);
            $table->string('depreciation_method', 20);
            $table->unsignedSmallInteger('useful_life_months');
            $table->decimal('declining_rate', 7, 4)->nullable();
            $table->decimal('accumulated_depreciation', 20, 4)->default(0);
            $table->unsignedSmallInteger('months_depreciated')->default(0);
            $table->date('depreciated_until')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('disposed_on')->nullable();
            $table->decimal('disposal_proceeds', 20, 4)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'asset_number']);
            $table->index(['company_id', 'status']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('asset_depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->date('period_end');
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->unsignedInteger('asset_count')->default(0);
            $table->string('status', 20)->default('POSTED');
            $table->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_journal_id')->nullable()->constrained('journals')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'period_end']);
        });

        Schema::create('asset_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('fixed_asset_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->date('transaction_date');
            $table->decimal('amount', 20, 4)->default(0);
            $table->foreignId('depreciation_run_id')->nullable()->constrained('asset_depreciation_runs')->restrictOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->json('details')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'type', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_transactions');
        Schema::dropIfExists('asset_depreciation_runs');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('asset_categories');
    }
};
