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
    | GitHub Token for Skill Discovery
    |--------------------------------------------------------------------------
    |
    | This option may be used to provide a GitHub Personal Access Token for
    | authenticating requests to the GitHub API when discovering skills from
    | remote repositories. This helps avoid rate limiting issues.
    |
    | You can generate a token at: https://github.com/settings/tokens
    | For public repositories, no scopes are required.
    |
    */

    'github_token' => env('BOOST_GITHUB_TOKEN'),

];
