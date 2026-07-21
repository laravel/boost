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
    | Boost Project Rules
    |--------------------------------------------------------------------------
    |
    | Project rules let agents record durable decisions, non-obvious traps, and
    | standing constraints as committed markdown in .ai/rules/. Boost also
    | extracts path-scoped guidelines there instead of inlining them.
    |
    */

    'rules' => [
        'enabled' => env('BOOST_RULES_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Boost Skills
    |--------------------------------------------------------------------------
    |
    | Here you may list the skills Boost ships to every project unconditionally,
    | such as "infer-conventions", to opt out of having them installed. This
    | is the only way to exclude a skill that Boost ships unconditionally.
    |
    */

    'skills' => [
        'exclude' => [],
    ],

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
    | Browser Log Levels
    |--------------------------------------------------------------------------
    |
    | This option controls which browser console log levels will be captured by
    | Boost's browser logger. You may trim this list down to ['error'] when
    | warnings, info, and debug messages become too noisy to be helpful.
    |
    */

    'browser_log_levels' => explode(',', env('BOOST_BROWSER_LOG_LEVELS', 'error,warning,info,debug')),

];
