<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('purpose', 50);
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
    }
};
