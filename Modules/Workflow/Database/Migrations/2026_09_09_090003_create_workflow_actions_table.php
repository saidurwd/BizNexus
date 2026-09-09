<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('from_state')->nullable();
            $table->string('to_state')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('acted_at')->useCurrent();

            $table->index(['workflow_instance_id', 'acted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_actions');
    }
};
