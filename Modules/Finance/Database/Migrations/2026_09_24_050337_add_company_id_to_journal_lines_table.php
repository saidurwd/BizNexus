<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('journal_id')->constrained()->restrictOnDelete();
        });

        DB::table('journal_lines')->update([
            'company_id' => DB::raw('(SELECT journals.company_id FROM journals WHERE journals.id = journal_lines.journal_id)'),
        ]);

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->index(['company_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'account_id']);
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
