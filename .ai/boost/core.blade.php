# Laravel Boost
@if($assist->hasMcpEnabled())

## Tools
- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL or MQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
@if (config('boost.browser_logs', false) !== false || config('boost.browser_logs_watcher', true) !== false)
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.
@endif

## Searching Documentation (IMPORTANT)
- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.
### Search Syntax
1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.
@endif

@if(config('boost.rules.enabled', true))
## Project Rules
- This project keeps committed, area-grouped rules in `.ai/rules` (settled decisions, non-obvious traps, standing constraints). Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule.
@if($assist->hasMcpEnabled())
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.
@endif

@endif

## Artisan
- Run Artisan commands directly via the command line (e.g., `{{ $assist->artisanCommand('route:list') }}`). Use `{{ $assist->artisanCommand('list') }}` to discover available commands and `{{ $assist->artisanCommand('[command] --help') }}` to check parameters.
- Inspect routes with `{{ $assist->artisanCommand('route:list') }}`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `{{ $assist->artisanCommand('config:show app.name') }}`, `{{ $assist->artisanCommand('config:show database.default') }}`. Or read config files directly from the `config/` directory.

## Tinker
- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
@if($assist->hasMcpEnabled() && config('boost.tinker_tool_enabled', false))
- Use the `tinker` MCP tool to execute PHP code instead of the CLI. It avoids shell escaping issues and runs the snippet in the Laravel application context.
@else
- Always use single quotes to prevent shell expansion: `{{ $assist->artisanCommand("tinker --execute 'Your::code();'") }}`
  - Double quotes for PHP strings inside: `{{ $assist->artisanCommand("tinker --execute 'User::where(\"active\", true)->count();'") }}`
 - Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

 ## URLs
 - Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

 ## Tinker / Debugging
 - You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
 - Use the `database-query` tool when you only need to read from the database.

+## Querying the Database
+- Connections can use different drivers (e.g. `mysql`, `pgsql`, `sqlite`, `mongodb`). If you don't already know the driver for the connection you're targeting, use the `database-connections` tool once to check before querying.
+- Use the `database-query` tool to read data directly. For SQL drivers, pass a read-only `query` argument. For the `mongodb` driver, pass a `command` argument instead (an MQL command document) — never SQL.
+
 @if (config('boost.browser_logs', true) !== false || config('boost.browser_logs_watcher', true) !== false)
 ## Reading Browser Logs With the `browser-logs` Tool
 - You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
 - Only recent browser logs will be useful - ignore old logs.
@endif
