# Upgrade Guide

## Upgrading To 2.x From 1.x

> Note: If you are not using custom agents or overriding Boost in any way, you should experience minimal issues while upgrading. Simply run `php artisan boost:install` after upgrading to Boost 2.x and the migration will be handled automatically.

> Note: If you are using external packages that add custom agents, ensure you update to versions that have support for Boost 2.x.

### Minimum PHP Version

PHP 8.2 is now the minimum required version.

### Minimum Laravel Version

Laravel 11.x is now the minimum required version.

### Custom Agent Changes

PR Link: https://github.com/laravel/boost/pull/439

Likelihood Of Impact: Low

If you have added your own custom agents, you will need to make the following changes:

#### Terminology and Namespace Changes

`CodeEnvironment` has been replaced with `Agent` throughout:

| Before | After |
|--------|-------|
| `CodeEnvironment` | `Agent` |
| `CodeEnvironmentsDetector` | `AgentsDetector` |
| `src/Install/CodeEnvironment/` | `src/Install/Agents/` |
| `Laravel\Boost\Install\CodeEnvironment` | `Laravel\Boost\Install\Agents` |

#### Contract Renames

Several contracts have been renamed for clarity:

| Before | After |
|--------|-------|
| `Laravel\Boost\Contracts\Agent` | `Laravel\Boost\Contracts\SupportsGuidelines` |
| `Laravel\Boost\Contracts\McpClient` | `Laravel\Boost\Contracts\SupportsMcp` |
| `Laravel\Boost\Contracts\SupportSkills` | `Laravel\Boost\Contracts\SupportsSkills` |

#### Custom Agent Migration

If you have registered custom agents, update them to use the new namespace and contracts:

Before:

```php
<?php

namespace App\Boost;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;

class MyCustomAgent extends CodeEnvironment implements Agent
{
    // ...
}
```

After:

```php
<?php

namespace App\Boost;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Install\Agents\Agent;

class MyCustomAgent extends Agent implements SupportsGuidelines
{
    // ...
}
```

If your agent also supports MCP or skills, add the additional contracts:

```php
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;

class MyCustomAgent extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    // ...
}
```

### Configuration File Changes

PR Link: https://github.com/laravel/boost/pull/439

Likelihood Of Impact: Low (Only applies if you have overridden configuration options in `config/boost.php`)

Published configuration paths have been updated from `code_environment` to `agents` in `config/boost.php`:

```diff
- config('boost.code_environment.junie.guidelines_path')
+ config('boost.agents.junie.guidelines_path')
```

This was previously undocumented, so the impact is very low unless you've explicitly overridden these configuration values.

### Installation Command Signature

PR Link: https://github.com/laravel/boost/pull/439

Likelihood Of Impact: Low

The `boost:install` command flags have changed from negative opt-out to positive opt-in for clearer intent:

```diff
- php artisan boost:install {--ignore-guidelines} {--ignore-mcp}
+ php artisan boost:install {--guidelines} {--skills} {--mcp}
```
