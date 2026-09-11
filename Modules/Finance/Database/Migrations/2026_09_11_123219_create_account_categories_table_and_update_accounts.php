<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'code']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('account_category_id')->nullable()->after('account_category');
            $table->foreign('account_category_id')->references('id')->on('account_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['account_category_id']);
            $table->dropColumn('account_category_id');
        });

        Schema::dropIfExists('account_categories');
    }
};
