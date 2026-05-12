# Laravel Boost
@if($assist->hasMcpEnabled())

## Tools
- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
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

## Artisan
- Run Artisan commands directly via the command line (e.g., `{{ $assist->artisanCommand('route:list') }}`). Use `{{ $assist->artisanCommand('list') }}` to discover available commands and `{{ $assist->artisanCommand('[command] --help') }}` to check parameters.
- Inspect routes with `{{ $assist->artisanCommand('route:list') }}`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `{{ $assist->artisanCommand('config:show app.name') }}`, `{{ $assist->artisanCommand('config:show database.default') }}`. Or read config files directly from the `config/` directory.

## Tinker
- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `{{ $assist->artisanCommand("tinker --execute 'Your::code();'") }}`
  - Double quotes for PHP strings inside: `{{ $assist->artisanCommand("tinker --execute 'User::where(\"active\", true)->count();'") }}`
