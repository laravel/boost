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
    | Agent-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | The following options may be used to configure agent-specific settings
    | such as file paths for guidelines, skills, and MCP configurations.
    |
    */

    'agents' => [
        'kilo_code' => [
            'guidelines_path' => env('BOOST_KILO_CODE_GUIDELINES_PATH', '.kilocode/rules'),
            'skills_path' => env('BOOST_KILO_CODE_SKILLS_PATH', '.kilocode/skills'),
        ],
    ],

];
