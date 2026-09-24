<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles are only ever assigned per company (company_user_roles). The global role_user table was never
 * populated and is dropped; company role assignments gain an optional validity window.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('role_user');

        Schema::table('company_user_roles', function (Blueprint $table) {
            $table->date('valid_from')->nullable()->after('status');
            $table->date('valid_until')->nullable()->after('valid_from');
        });
    }

    public function down(): void
    {
        Schema::table('company_user_roles', function (Blueprint $table) {
            $table->dropColumn(['valid_from', 'valid_until']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });
    }
};
