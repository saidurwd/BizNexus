<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduces tenants (customer organisations) above companies. Existing companies and users move into a
 * default tenant; company codes become unique per tenant instead of globally.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('data_region', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        $defaultTenantId = DB::table('tenants')->insertGetId([
            'code' => 'DEFAULT', 'name' => 'Default', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (['companies', 'users'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('tenant_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            });

            DB::table($table)->update(['tenant_id' => $defaultTenantId]);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('tenant_id')->nullable(false)->change();
            });
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'code']);
            $table->unique('code');
        });

        foreach (['companies', 'users'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropConstrainedForeignId('tenant_id'));
        }

        Schema::dropIfExists('tenants');
    }
};
