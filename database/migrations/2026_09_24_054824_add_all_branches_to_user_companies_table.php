<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_companies', function (Blueprint $table) {
            $table->boolean('all_branches')->default(false)->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('user_companies', function (Blueprint $table) {
            $table->dropColumn('all_branches');
        });
    }
};
