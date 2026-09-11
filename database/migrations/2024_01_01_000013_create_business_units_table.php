<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_units');
    }
};
