<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Region of this Deployment
    |--------------------------------------------------------------------------
    |
    | Each regional deployment (e.g. "eu", "me", "bd") serves only tenants whose
    | data_region matches, so tenant data stays in its jurisdiction. Leave empty
    | for a single-region installation.
    |
    */

    'data_region' => env('DATA_REGION'),

];
