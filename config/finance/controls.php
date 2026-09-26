<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Segregation of Duties
    |--------------------------------------------------------------------------
    |
    | When enabled, the user who created a journal cannot approve it, and the
    | user who approved a journal cannot post it.
    |
    */

    'creator_cannot_approve' => env('FINANCE_CREATOR_CANNOT_APPROVE', true),

    'approver_cannot_post' => env('FINANCE_APPROVER_CANNOT_POST', false),

    /*
    |--------------------------------------------------------------------------
    | Budget Control
    |--------------------------------------------------------------------------
    |
    | "block" rejects posting an expense that exceeds its active budget line,
    | "off" posts without checking the budget.
    |
    */

    'budget_control' => env('FINANCE_BUDGET_CONTROL', 'block'),

    /*
    |--------------------------------------------------------------------------
    | Customer Credit Limits
    |--------------------------------------------------------------------------
    |
    | "block" refuses to submit an invoice that takes a customer's open
    | receivables above their credit limit, "warn" allows it with a message,
    | "off" ignores credit limits. Customers without a limit are not checked.
    |
    */

    'credit_limit' => env('FINANCE_CREDIT_LIMIT', 'block'),

];
