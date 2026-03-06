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
    |
    */

    'browser_logs_watcher' => env('BOOST_BROWSER_LOGS_WATCHER', true),

    /*
    |--------------------------------------------------------------------------
    | Boost Executables Paths
    |--------------------------------------------------------------------------
    |
    | These options allow you to specify custom paths for the executables that
    | Boost uses. When configured, they take precedence over the automatic
    | discovery mechanism. Leave empty to use defaults from your $PATH.
    |
    */

    'executable_paths' => [
        'php' => env('BOOST_PHP_EXECUTABLE_PATH'),
        'composer' => env('BOOST_COMPOSER_EXECUTABLE_PATH'),
        'npm' => env('BOOST_NPM_EXECUTABLE_PATH'),
        'vendor_bin' => env('BOOST_VENDOR_BIN_EXECUTABLE_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Adaptive Context Engine (ACE)
    |--------------------------------------------------------------------------
    |
    | ACE consolidates MCP tools and guidelines into a compact manifest
    | with batched context resolution. Skills remain delivered via
    | .claude/skills/ files and are not affected by ACE.
    |
    | When legacy_tools is false (default), ACE replaces the 15 legacy
    | tools with a 3-tool interface (manifest, resolve, execute) for
    | significant token reduction. Set legacy_tools to true to keep
    | legacy tools alongside ACE during a gradual migration.
    |
    */

    'ace' => [
        'enabled' => env('BOOST_ACE_ENABLED', false),
        'legacy_tools' => false,
        'bundles' => [
            'exclude' => [],
        ],
    ],

];
