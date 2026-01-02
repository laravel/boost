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
    | Code Environment Configuration
    |--------------------------------------------------------------------------
    |
    | Configure code environment-specific settings such as custom guideline
    | paths for different AI agents and editors. Each code environment can
    | define a custom path where its guidelines should be written.
    |
    */

    'code_environments' => [

        'claude_code' => [
            'guidelines_path' => env('BOOST_CLAUDE_CODE_GUIDELINES_PATH', 'CLAUDE.md'),
        ],

        'cursor' => [
            'guidelines_path' => env('BOOST_CURSOR_GUIDELINES_PATH', '.cursor/rules/laravel-boost.mdc'),
        ],

        'copilot' => [
            'guidelines_path' => env('BOOST_COPILOT_GUIDELINES_PATH', '.github/copilot-instructions.md'),
        ],

        'gemini' => [
            'guidelines_path' => env('BOOST_GEMINI_GUIDELINES_PATH', 'GEMINI.md'),
        ],

        'codex' => [
            'guidelines_path' => env('BOOST_CODEX_GUIDELINES_PATH', 'AGENTS.md'),
        ],

        'opencode' => [
            'guidelines_path' => env('BOOST_OPENCODE_GUIDELINES_PATH', 'AGENTS.md'),
        ],

        'phpstorm' => [
            'guidelines_path' => env('BOOST_PHPSTORM_GUIDELINES_PATH', '.junie/guidelines.md'),
        ],

    ],

];
