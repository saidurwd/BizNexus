<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automatic Depreciation
    |--------------------------------------------------------------------------
    |
    | When true, the scheduler posts the previous month's depreciation for every
    | company on the 1st of each month at 04:00 (php artisan assets:depreciate).
    | Off by default so accountants review and post runs themselves.
    |
    */

    'auto_depreciation' => env('ASSETS_AUTO_DEPRECIATION', false),

];
