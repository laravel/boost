# Upgrade Guide

## Upgrading To 2.x From 1.x

> Note: If you are not using custom agents or overriding Boost in any way, you should experience minimal issues while upgrading. Simply run `php artisan boost:install` after upgrading to Boost 2.x and the migration will be handled automatically.
>
> Important: If you are using external packages that add custom agents, ensure you update to versions that have support for Boost 2.x.

### Agent Consolidation

PR Link: https://github.com/laravel/boost/pull/439

Likelihood Of Impact: Medium

Boost 2.x focuses on configuring AI agents rather than IDEs. Update agent references:

| Before | After |
|--------|-------|
| `PhpStorm` | `Junie` |
| `VSCode` | `Copilot` |

### Custom Agent Changes

PR Link: https://github.com/laravel/boost/pull/439

Likelihood Of Impact: Low (only if you have custom agents)

If you have added your own custom agents, you'll need to make the following changes:

#### Terminology & Namespace Changes

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

Likelihood Of Impact: Medium (if you've overridden config), Low (otherwise)

Published configuration paths have been updated from `code_environment` to `agents`. For example:

```diff
- config('boost.code_environment.junie.guidelines_path')
+ config('boost.agents.junie.guidelines_path')
```

This was previously undocumented, so the impact is very low unless you've explicitly overridden these configuration values.

### Installation Command Signature

PR Link: https://github.com/laravel/boost/pull/439

Likelihood Of Impact: Low (UX improvement)

The `boost:install` command flags have changed from negative opt-out to positive opt-in for clearer intent:

```diff
- php artisan boost:install {--ignore-guidelines} {--ignore-mcp}
+ php artisan boost:install {--guidelines} {--skills} {--mcp}
```

This is a UX improvement and does not affect programmatic usage if you were running the command without flags.
