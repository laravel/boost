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
    | Boost Commands
    |--------------------------------------------------------------------------
    |
    | The following options allow you to configure custom paths for the PHP
    | and Composer binaries used by Boost. Leave empty to use defaults.
    | When configured, these take precedence over automatic detection.
    |
    */

    'commands' => [
        'php' => env('BOOST_PHP_BINARY'),
        'composer' => env('BOOST_COMPOSER_BINARY'),
        'npm' => env('BOOST_NPM_BINARY'),
    ],

];
