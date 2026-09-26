<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log Retention (days)
    |--------------------------------------------------------------------------
    |
    | Older rows are pruned daily by model:prune. Keep sign-in history and
    | security events long enough for investigations and your regulator.
    |
    */

    'retention_days' => [
        'login_history' => (int) env('SECURITY_LOGIN_HISTORY_DAYS', 730),
        'security_events' => (int) env('SECURITY_EVENTS_DAYS', 730),
        'activity_logs' => (int) env('SECURITY_ACTIVITY_LOG_DAYS', 365),
        'scheduled_task_runs' => (int) env('SCHEDULED_TASK_RUN_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Logging
    |--------------------------------------------------------------------------
    |
    | Every change a signed-in user makes (POST, PUT, PATCH, DELETE) is logged,
    | plus downloads of data whose route name ends with one of these suffixes.
    |
    */

    'logged_download_routes' => ['.export', '.pdf', '.e-invoice', '.download'],

];
