<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tax engine: jurisdiction and recoverability on tax codes, effective-dated rates, tax groups with
 * compound components, determination rules, country codes on parties, and reverse charge / withholding.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->string('tax_type', 30)->default('VAT')->change();
            $table->char('country_code', 2)->nullable()->after('tax_type');
            $table->string('region_code', 10)->nullable()->after('country_code');
            $table->boolean('is_group')->default(false)->after('is_inclusive');
            $table->boolean('is_recoverable')->default(true)->after('is_group');
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_id')->constrained()->cascadeOnDelete();
            $table->decimal('rate', 10, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['tax_id', 'effective_from']);
        });

        DB::table('taxes')->orderBy('id')->each(fn (object $tax) => DB::table('tax_rates')->insert([
            'tax_id' => $tax->id, 'rate' => $tax->rate, 'effective_from' => '1900-01-01', 'created_at' => now(), 'updated_at' => now(),
        ]));

        Schema::create('tax_group_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_tax_id')->constrained('taxes')->cascadeOnDelete();
            $table->foreignId('component_tax_id')->constrained('taxes')->restrictOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->boolean('is_compound')->default(false);
            $table->timestamps();

            $table->unique(['group_tax_id', 'component_tax_id']);
        });

        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('direction', 10);
            $table->char('counterparty_country', 2)->nullable();
            $table->string('counterparty_type', 10)->default('any');
            $table->string('supply_type', 10)->default('any');
            $table->foreignId('tax_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('reverse_charge')->default(false);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->timestamps();

            $table->index(['company_id', 'direction']);
        });

        foreach (['companies', 'customers', 'suppliers'] as $partyTable) {
            Schema::table($partyTable, fn (Blueprint $table) => $table->char('country_code', 2)->nullable()->after('tax_number'));
        }

        foreach (['supplier_invoice_lines', 'customer_invoice_lines'] as $lineTable) {
            Schema::table($lineTable, function (Blueprint $table) {
                $table->string('supply_type', 10)->nullable()->after('tax_id');
                $table->boolean('is_reverse_charge')->default(false)->after('supply_type');
            });
        }

        Schema::table('tax_transactions', function (Blueprint $table) {
            $table->foreignId('customer_invoice_id')->nullable()->after('invoice_id')->constrained('customer_invoices')->restrictOnDelete();
            $table->boolean('is_reverse_charge')->default(false)->after('transaction_type');
            $table->boolean('is_recoverable')->default(true)->after('is_reverse_charge');
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->foreignId('withholding_tax_id')->nullable()->after('amount')->constrained('taxes')->restrictOnDelete();
            $table->decimal('withholding_amount', 20, 4)->default(0)->after('withholding_tax_id');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withholding_tax_id');
            $table->dropColumn('withholding_amount');
        });
        Schema::table('tax_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_invoice_id');
            $table->dropColumn(['is_reverse_charge', 'is_recoverable']);
        });
        foreach (['supplier_invoice_lines', 'customer_invoice_lines'] as $lineTable) {
            Schema::table($lineTable, fn (Blueprint $table) => $table->dropColumn(['supply_type', 'is_reverse_charge']));
        }
        foreach (['companies', 'customers', 'suppliers'] as $partyTable) {
            Schema::table($partyTable, fn (Blueprint $table) => $table->dropColumn('country_code'));
        }
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('tax_group_components');
        Schema::dropIfExists('tax_rates');
        Schema::table('taxes', fn (Blueprint $table) => $table->dropColumn(['country_code', 'region_code', 'is_group', 'is_recoverable']));
    }
};
