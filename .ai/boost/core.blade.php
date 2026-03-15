# Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands
- Run Artisan commands directly via the command line (e.g., `{{ $assist->artisanCommand('route:list') }}`, `{{ $assist->artisanCommand("tinker --execute '...'") }}`).
- Use `{{ $assist->artisanCommand('list') }}` to discover available commands and `{{ $assist->artisanCommand('[command] --help') }}` to check parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- Use tinker to execute PHP in the application context for debugging, checking functions, and testing code. Do not create models without user approval — prefer Unit/Feature tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always wrap tinker code in **single quotes** to prevent shell variable expansion: `{{ $assist->artisanCommand("tinker --execute 'Your::code();'") }}`
  - Use double quotes for PHP strings inside: `{{ $assist->artisanCommand("tinker --execute 'User::where(\"active\", true)->count();'") }}`
  - If the PHP code needs literal single quotes, escape them as `'\''`.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Configuration & Environment
- Read configuration values using dot notation: `{{ $assist->artisanCommand('config:show app.name') }}`, `{{ $assist->artisanCommand('config:show database.default') }}`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Routes
- Inspect routes with `{{ $assist->artisanCommand('route:list') }}`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.

@if (config('boost.browser_logs', false) !== false || config('boost.browser_logs_watcher', true) !== false)
## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.
@endif

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.
