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
    | Process Isolation for Tools
    |--------------------------------------------------------------------------
    |
    | When enabled, tools like Tinker will run in isolated processes to prevent
    | timeouts from affecting the main MCP server. This provides better stability
    | and protection against runaway code execution.
    */

    'process_isolation' => [
        'enabled' => env('BOOST_PROCESS_ISOLATION', true),  // Default ON to prevent MCP server crashes
        'timeout' => env('BOOST_PROCESS_TIMEOUT', 120),     // 2 minutes default
    ],

];
