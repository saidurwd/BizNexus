---
paths:
  - 'Modules/Assets/**'
---

# Assets

## Fixed assets change only through FixedAssetService and DepreciationService, each event recorded
Every change to an asset's value is recorded as an AssetTransaction (acquisition, opening_depreciation, depreciation, impairment, disposal, transfer) with its journal. The register and movement reports are rebuilt from these transactions for any date, so never update cost or accumulated_depreciation on a FixedAsset directly. Depreciation is monthly from the in-service month, rounded per month. Straight line spreads (carrying amount - residual) over the remaining months. Capitalised purchases (supplier invoice or goods receipt lines on a category's asset account) post no journal, because the purchase already debited the asset account.
