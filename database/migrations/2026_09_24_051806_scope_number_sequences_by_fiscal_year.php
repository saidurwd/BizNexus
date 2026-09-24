<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sequences are kept per fiscal year; the original (company, document type) unique index made a
 * second fiscal year's sequence impossible to create.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('number_sequences', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::table('number_sequences', function (Blueprint $table) {
            $table->unique(['company_id', 'document_type']);
        });
    }
};
