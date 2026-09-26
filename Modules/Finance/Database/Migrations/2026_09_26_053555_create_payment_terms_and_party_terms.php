<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payment terms (due days from the invoice date or from the end of its month, with an optional early-payment
 * discount), a default term on customers and suppliers, and a credit limit on customers. Existing companies get
 * the usual terms.
 */
return new class extends Migration
{
    /**
     * @var list<array{code: string, name: string, due_days: int, due_basis: string}>
     */
    public const DEFAULT_TERMS = [
        ['code' => 'DUE', 'name' => 'Due on receipt', 'due_days' => 0, 'due_basis' => 'invoice_date'],
        ['code' => 'NET15', 'name' => 'Net 15 days', 'due_days' => 15, 'due_basis' => 'invoice_date'],
        ['code' => 'NET30', 'name' => 'Net 30 days', 'due_days' => 30, 'due_basis' => 'invoice_date'],
        ['code' => 'NET60', 'name' => 'Net 60 days', 'due_days' => 60, 'due_basis' => 'invoice_date'],
        ['code' => 'EOM30', 'name' => '30 days end of month', 'due_days' => 30, 'due_basis' => 'end_of_month'],
    ];

    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->unsignedSmallInteger('due_days')->default(0);
            $table->string('due_basis', 20)->default('invoice_date');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('discount_days')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('payment_term_id')->nullable()->after('currency_id')->constrained()->nullOnDelete();
            $table->decimal('credit_limit', 20, 4)->nullable()->after('payment_term_id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('payment_term_id')->nullable()->after('currency_id')->constrained()->nullOnDelete();
        });

        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('payment_terms')->insert(array_map(fn (array $term) => [...$term, 'company_id' => $companyId, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now], self::DEFAULT_TERMS));
        }
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_term_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_term_id');
            $table->dropColumn('credit_limit');
        });

        Schema::dropIfExists('payment_terms');
    }
};
