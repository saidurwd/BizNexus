<?php

namespace Modules\Assets\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Assets\Models\AssetCategory;
use Modules\Assets\Models\FixedAsset;
use Modules\Assets\Services\FixedAssetService;
use Modules\Finance\Controllers\Controller;

/**
 * Purchases posted to an asset account that are not yet in the asset register.
 */
class CapitalisationController extends Controller
{
    public function index(FixedAssetService $assets): View
    {
        return view('assets.capitalise.index', [
            'lines' => $assets->capitalisableLines(),
            'categories' => AssetCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, FixedAssetService $assets): RedirectResponse
    {
        $validated = $request->validate([
            'source_type' => ['required', Rule::in([FixedAsset::SOURCE_SUPPLIER_INVOICE_LINE, FixedAsset::SOURCE_GOODS_RECEIPT_LINE])],
            'source_id' => ['required', 'integer'],
            'category_id' => ['required', Rule::exists('asset_categories', 'id')->where('company_id', $this->getActiveCompanyId())],
            'name' => ['nullable', 'string', 'max:255'],
            'in_service_date' => ['nullable', 'date'],
        ]);

        $asset = $assets->capitalise($validated['source_type'], (int) $validated['source_id'], [...$validated, 'company_id' => $this->getActiveCompanyId()]);

        return redirect()->route('assets.assets.show', $asset->id)->with('success', __('Asset :number registered.', ['number' => $asset->asset_number]));
    }
}
