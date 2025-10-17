<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Boost PHP Binary
    |--------------------------------------------------------------------------
    |
    | This is useful if you want to expose the MCP server via HTTP
    | because you still need the CLI version of PHP to run certain
    | Boost commands such as log readers.
    */
    'php_binary' => env('BOOST_PHP_BINARY', PHP_BINARY),

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

];
