<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Negative Stock
    |--------------------------------------------------------------------------
    |
    | When false, an issue, transfer or sale that would take a warehouse below
    | zero is refused. Allowing negative stock values the shortfall at the
    | current average cost until the goods are received.
    |
    */

    'allow_negative_stock' => env('INVENTORY_ALLOW_NEGATIVE_STOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Three-Way Match Price Tolerance
    |--------------------------------------------------------------------------
    |
    | How far (in percent) a supplier invoice's unit price may differ from the
    | purchase order price before submitting the invoice is refused. The
    | difference within tolerance is posted to the purchase price variance
    | account.
    |
    */

    'price_tolerance_percent' => env('INVENTORY_PRICE_TOLERANCE_PERCENT', 5),

];
