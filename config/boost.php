<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Boost Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable all Boost functionality - which
    | will prevent Boost's routes from being registered and will also
    | disable Boost's browser logging functionality from operating.
    |
    */

    'enabled' => env('BOOST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Boost Browser Logs Watcher
    |--------------------------------------------------------------------------
    |
    | The following option may be used to enable or disable the browser logs
    | watcher feature within Laravel Boost. The log watcher will read any
    | errors within the browser's console to give Boost better context.
    */

    'browser_logs_watcher' => env('BOOST_BROWSER_LOGS_WATCHER', true),

    /*
    |--------------------------------------------------------------------------
    | Telemetry
    |--------------------------------------------------------------------------
    |
    | Boost collects anonymous usage telemetry to help improve the tool.
    | Only tool names and invocation counts are collected - no file paths,
    | code, or identifying information is ever sent to telemetry.
    |
    */

    'telemetry' => [
        'enabled' => env('BOOST_TELEMETRY_ENABLED', true),
        'url' => env('BOOST_TELEMETRY_URL', 'https://boost.laravel.com/api/telemetry'),
    ],

];
