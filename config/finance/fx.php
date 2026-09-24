<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum Spot Rate Age
    |--------------------------------------------------------------------------
    |
    | A transaction may use the most recent spot rate on or before its date,
    | but not one older than this many days (weekends, bank holidays).
    |
    */

    'max_spot_rate_age_days' => (int) env('FINANCE_MAX_SPOT_RATE_AGE_DAYS', 7),

];
