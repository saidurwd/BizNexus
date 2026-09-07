<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('due_date');
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 6)->nullable()->after('currency_id');
            $table->decimal('outstanding_amount', 20, 4)->nullable()->after('total_amount');
            $table->unsignedBigInteger('journal_id')->nullable()->after('outstanding_amount');
            $table->foreign('journal_id')->references('id')->on('journals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropForeign(['journal_id']);
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'outstanding_amount', 'journal_id']);
        });
    }
};
