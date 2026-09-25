# Laravel Boost
@if($assist->hasMcpEnabled())

## Tools
- Always use Laravel Boost MCP tools instead of CLI commands or manual file reads before making any change, e.g. `database-schema` before migrations or models, `database-query` instead of raw SQL in tinker, and `get-absolute-url` before sharing a URL.
- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant.
@endif

@if(config('boost.rules.enabled', true))
## Project Rules
- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
@endif

## Artisan
- Inspect routes with `{{ $assist->artisanCommand('route:list') }}`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `{{ $assist->artisanCommand('config:show app.name') }}`, `{{ $assist->artisanCommand('config:show database.default') }}`. Or read config files directly from the `config/` directory.

## Tinker
- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
@if($assist->hasMcpEnabled() && config('boost.tinker_tool_enabled', false))
- Use the `tinker` MCP tool to execute PHP code instead of the CLI. It avoids shell escaping issues and runs the snippet in the Laravel application context.
@else
- Always use single quotes to prevent shell expansion: `{{ $assist->artisanCommand("tinker --execute 'Your::code();'") }}`
  - Double quotes for PHP strings inside: `{{ $assist->artisanCommand("tinker --execute 'User::where(\"active\", true)->count();'") }}`
@endif
