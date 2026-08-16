<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Boost Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable all Boost functionality which will
    | prevent Boost's routes from being registered and will also disable
    | Boost's browser logging functionality from reading or operating.
    |
    */

    'enabled' => env('BOOST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Application Purpose
    |--------------------------------------------------------------------------
    |
    | A short description of what your application does. When set, Boost adds
    | it to the top of the guidelines it generates so agents know what they
    | are working on before they read any of your application's code.
    |
    */

    'purpose' => env('BOOST_PURPOSE'),

    /*
    |--------------------------------------------------------------------------
    | Boost Project Rules
    |--------------------------------------------------------------------------
    |
    | Project rules let agents write decisions, traps and standing constraints
    | as tracked Markdown in /.ai/rules/. Enabling "scoped_guidelines" also
    | moves path-scoped guidelines to .ai/rules/boost/ - it stays opt-in.
    |
    */

    'rules' => [
        'enabled' => env('BOOST_RULES_ENABLED', true),
        'scoped_guidelines' => env('BOOST_RULES_SCOPED_GUIDELINES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Guidelines
    |--------------------------------------------------------------------------
    |
    | Any guidelines listed here will be excluded whenever Boost composes your
    | AI guidelines during boost:install or boost:update. Entries match the
    | names shown within the boost:install summary, e.g. "livewire/core".
    |
    */

    'guidelines' => [
        'exclude' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Skills
    |--------------------------------------------------------------------------
    |
    | Any skills listed here will not be installed or synced to your agents
    | by boost:install and boost:update, e.g. "fluxui-development". Your
    | own skills within the ".ai/skills" directory are never excluded.
    |
    */

    'skills' => [
        'exclude' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub Token
    |--------------------------------------------------------------------------
    |
    | Boost sends this token when downloading remote skills with the
    | boost:add-skill command, raising GitHub's rate limit and allowing
    | private repositories to be read. Falls back to services.github.token.
    |
    */

    'github' => [
        'token' => env('BOOST_GITHUB_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Enforce Tests
    |--------------------------------------------------------------------------
    |
    | This option determines whether Boost includes its guideline instructing
    | agents to write tests. When left null, Boost decides for you based on
    | whether your application already has a working test suite set up.
    |
    */

    'enforce_tests' => null,

    /*
    |--------------------------------------------------------------------------
    | Boost MCP Server
    |--------------------------------------------------------------------------
    |
    | Each MCP tool runs in its own subprocess and is stopped once the timeout
    | below is reached. You may also drop any of Boost's bundled tools, or
    | register tool classes of your own, using the two arrays below.
    |
    */

    'mcp' => [
        'tool_timeout' => 180,

        'tools' => [
            'include' => [],
            'exclude' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tinker Tool
    |--------------------------------------------------------------------------
    |
    | The Tinker MCP tool lets agents execute arbitrary PHP within your
    | application, so it is not registered unless you opt in here. Only
    | enable it when you are comfortable with agents running code.
    |
    */

    'tinker_tool_enabled' => env('BOOST_TINKER_TOOL_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Boost Executables Paths
    |--------------------------------------------------------------------------
    |
    | These options allow you to specify custom paths for the executables that
    | Boost uses. While configured, they take precedence over the automatic
    | discovery mechanism. When undefined, your system defaults are used.
    |
    */

    'executable_paths' => [
        'php' => env('BOOST_PHP_EXECUTABLE_PATH'),
        'composer' => env('BOOST_COMPOSER_EXECUTABLE_PATH'),
        'npm' => env('BOOST_NPM_EXECUTABLE_PATH'),
        'vendor_bin' => env('BOOST_VENDOR_BIN_EXECUTABLE_PATH'),
        'sail' => env('BOOST_SAIL_EXECUTABLE_PATH'),
        'current_directory' => env('BOOST_CURRENT_DIRECTORY_EXECUTABLE_PATH'),
    ],

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
    | Browser Log Levels
    |--------------------------------------------------------------------------
    |
    | This option defines which browser console log levels will be captured by
    | Boost's browser logger. You may trim this list down to ['error'] when
    | warnings, info, and debug messages become too noisy to be relevant.
    |
    */

    'browser_log_levels' => explode(',', env('BOOST_BROWSER_LOG_LEVELS', 'error,warning,info,debug')),

];
