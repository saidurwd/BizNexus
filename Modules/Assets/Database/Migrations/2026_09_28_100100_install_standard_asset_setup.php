<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Assets\Support\StandardAssetSetup;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            StandardAssetSetup::installFor((int) $companyId);
        }
    }

    public function down(): void
    {
        DB::table('asset_categories')->whereIn('code', ['EQUIP', 'FURN', 'VEH'])->whereNotIn('id', DB::table('fixed_assets')->select('category_id'))->delete();
    }
};
