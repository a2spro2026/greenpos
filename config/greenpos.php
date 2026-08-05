<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Demo auto-login (local / testing only)
    |--------------------------------------------------------------------------
    | When true, guests are signed in as admin@greenpos.test in local/testing.
    | Disabled after an explicit logout until the next successful login
    | (cookie gp_auto_login_blocked).
    */
    'auto_login' => (bool) env('GREENPOS_AUTO_LOGIN', true),

    /*
    |--------------------------------------------------------------------------
    | Session lock idle hint (minutes) — informational for UI
    |--------------------------------------------------------------------------
    */
    'lock_idle_minutes' => (int) env('GREENPOS_LOCK_IDLE_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Last account cookie (for “Continuer avec ce compte”)
    |--------------------------------------------------------------------------
    */
    'last_account_cookie' => 'gp_last_account',
    'last_account_days' => 30,
    'auto_login_block_cookie' => 'gp_auto_login_blocked',
];
