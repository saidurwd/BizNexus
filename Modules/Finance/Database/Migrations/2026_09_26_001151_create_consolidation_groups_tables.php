<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consolidation_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('consolidation_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consolidation_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->decimal('ownership_percent', 7, 4)->default(100);
            $table->timestamps();

            $table->unique(['consolidation_group_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consolidation_group_members');
        Schema::dropIfExists('consolidation_groups');
    }
};
