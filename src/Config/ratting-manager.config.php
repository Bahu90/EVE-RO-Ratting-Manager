<?php

/*
|--------------------------------------------------------------------------
| Ratting Manager - Configuration
|--------------------------------------------------------------------------
|
| Everything an operator changes lives on the Settings page. These are the
| few values that only matter to an install, not to a corporation.
|
*/

return [

    // Public ESI, for names of characters SeAT holds no token for.
    'esi_base_url' => env('RATTING_MANAGER_ESI_URL', 'https://esi.evetech.net/latest'),

    // How long dashboard and leaderboard figures are kept before they are
    // worked out again. The journals only move every half hour, so a few
    // minutes costs nothing and saves the heaviest queries on busy pages.
    'cache_seconds' => 300,

];
