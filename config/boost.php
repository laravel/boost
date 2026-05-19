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
        'current_directory' => env('BOOST_CURRENT_DIRECTORY_EXECUTABLE_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Boost MCP Transports
    |--------------------------------------------------------------------------
    |
    | Boost registers a stdio (local) MCP server by default, exposed through
    | the `php artisan boost:mcp` command. Some MCP clients do not keep
    | stdio servers alive reliably, so Boost may also expose its MCP server
    | over HTTP (Streamable HTTP) using laravel/mcp's web transport.
    |
    | The HTTP transport is disabled by default. Enabling it exposes Boost's
    | development tooling (database access, log access, route inspection,
    | tinker, etc.) over an HTTP endpoint, so it should only be enabled in
    | trusted local development environments and ideally protected with
    | application middleware (for example `auth:sanctum`) when reachable
    | over a network.
    |
    */

    'mcp' => [

        'web' => [

            'enabled' => env('BOOST_MCP_WEB_ENABLED', false),

            'path' => env('BOOST_MCP_WEB_PATH', '/_boost/mcp'),

            /*
            | Middleware applied to the HTTP MCP route. Leave empty to inherit
            | only the middleware that laravel/mcp applies internally. Add
            | application-appropriate middleware (e.g. `auth:sanctum`) when
            | exposing the endpoint outside of a trusted local environment.
            */

            'middleware' => [],

        ],

    ],

];
