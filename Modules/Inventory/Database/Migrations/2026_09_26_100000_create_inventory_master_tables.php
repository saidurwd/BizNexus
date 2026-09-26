<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Units every company starts with (kept in step with Unit::DEFAULTS).
     *
     * @var list<array{code: string, name: string, decimals: int}>
     */
    protected const DEFAULT_UNITS = [
        ['code' => 'EA', 'name' => 'Each', 'decimals' => 0],
        ['code' => 'BOX', 'name' => 'Box', 'decimals' => 0],
        ['code' => 'KG', 'name' => 'Kilogram', 'decimals' => 3],
        ['code' => 'L', 'name' => 'Litre', 'decimals' => 3],
        ['code' => 'M', 'name' => 'Metre', 'decimals' => 2],
        ['code' => 'HR', 'name' => 'Hour', 'decimals' => 2],
    ];

    public function up(): void
    {
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code', 10);
            $table->string('name', 100);
            $table->unsignedTinyInteger('decimals')->default(0);
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('revenue_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('sku', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 10)->default('stock');
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->string('barcode', 50)->nullable();
            $table->decimal('purchase_price', 20, 4)->default(0);
            $table->decimal('sales_price', 20, 4)->default(0);
            $table->foreignId('purchase_tax_id')->nullable()->constrained('taxes')->restrictOnDelete();
            $table->foreignId('sales_tax_id')->nullable()->constrained('taxes')->restrictOnDelete();
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('revenue_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('reorder_level', 20, 4)->nullable();
            $table->decimal('reorder_quantity', 20, 4)->nullable();
            $table->string('costing_method', 20)->default('weighted_average');
            $table->decimal('stock_quantity', 20, 4)->default(0);
            $table->decimal('stock_value', 20, 4)->default(0);
            $table->string('status', 10)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'barcode']);
        });

        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('units_of_measure')->insert(array_map(fn (array $unit) => [...$unit, 'company_id' => $companyId, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now], self::DEFAULT_UNITS));
            DB::table('warehouses')->insert(['company_id' => $companyId, 'code' => 'MAIN', 'name' => 'Main warehouse', 'is_default' => true, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('units_of_measure');
    }
};
