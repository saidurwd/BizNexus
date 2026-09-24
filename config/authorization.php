<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conflicting Permissions (Segregation of Duties)
    |--------------------------------------------------------------------------
    |
    | Pairs of permissions one user must not hold together in the same company.
    | Roles and role assignments creating such a combination are refused; run
    | "php artisan authorization:sod-report" to list existing violations.
    |
    */

    'conflicting_permissions' => [
        ['finance.suppliers.create', 'finance.payments.approve'],
        ['finance.suppliers.update', 'finance.payments.approve'],
        ['finance.customers.create', 'finance.receipts.approve'],
    ],

];
