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
    | Installation Defaults
    |--------------------------------------------------------------------------
    |
    | These options control the default behavior during boost:install command.
    | You can override these via environment variables or use them as defaults
    | for non-interactive installations.
    */

    'install' => [
        'mcp_server' => env('BOOST_MCP_SERVER', true),
        'ai_guidelines' => env('BOOST_AI_GUIDELINES', true),
        'herd' => env('BOOST_HERD', false),
        'enforce_tests' => env('BOOST_ENFORCE_TESTS'),
        'agents' => env('BOOST_AGENTS'),
        'editors' => env('BOOST_EDITORS'),
    ],

];
