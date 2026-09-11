<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            
            $table->unique(['company_id', 'code']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
