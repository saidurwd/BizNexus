<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tables no code has ever read or written: customer debit notes (additional charges are invoices), account
 * balances (balances come from posted journal lines) and profit centers (never wired to postings). Each is dropped
 * only when empty, so a database that used them some other way keeps its data.
 */
return new class extends Migration
{
    protected const TABLES = ['customer_debit_notes', 'account_balances', 'profit_centers'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->doesntExist()) {
                Schema::drop($table);
            }
        }
    }

    public function down(): void
    {
        // Irreversible: the tables were unused. Roll back the migrations that created them to recreate them.
    }
};
