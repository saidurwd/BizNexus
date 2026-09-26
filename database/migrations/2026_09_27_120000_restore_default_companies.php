<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Editing a user used to clear their default company. Give every user who has companies but no default
 * their first company back as the default.
 */
return new class extends Migration
{
    public function up(): void
    {
        $usersWithDefault = DB::table('user_companies')->where('is_default', true)->pluck('user_id')->unique();

        DB::table('user_companies')
            ->whereNotIn('user_id', $usersWithDefault)
            ->where('status', 'active')
            ->selectRaw('user_id, MIN(id) AS first_link_id')
            ->groupBy('user_id')
            ->pluck('first_link_id')
            ->each(fn (int $id) => DB::table('user_companies')->where('id', $id)->update(['is_default' => true]));
    }

    public function down(): void
    {
        // Defaults set here are indistinguishable from ones users chose; nothing to undo.
    }
};
