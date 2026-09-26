<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('locale', 10)->nullable()->after('email'));
        Schema::table('companies', fn (Blueprint $table) => $table->string('locale', 10)->default('en')->after('timezone'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('locale'));
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('locale'));
    }
};
