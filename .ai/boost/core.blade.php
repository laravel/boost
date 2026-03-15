# Laravel Boost

## Tools
- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.

## Documentation (IMPORTANT)
- **Your knowledge of Laravel and its ecosystem packages may be outdated. Always use `search-docs` to get current documentation, examples, and correct API usage before writing or modifying code.**
- `search-docs` automatically includes installed package versions — do not add package names to queries. Use multiple broad, topic-based queries: `['rate limiting', 'routing']`.
- Syntax: words use AND logic, `"quoted phrases"` match exact position, multiple `queries=[]` use OR logic.
- `database-query`: Read-only database queries.
- `database-schema`: Inspect table structure before writing migrations or models.
- `get-absolute-url`: Get correct scheme, domain, and port for project URLs. Use whenever sharing a URL with the user.
@if (config('boost.browser_logs', false) !== false || config('boost.browser_logs_watcher', true) !== false)
- `browser-logs`: Read browser logs, errors, and exceptions. Only recent logs are useful.
@endif

## Artisan
- Run Artisan commands directly via the command line (e.g., `{{ $assist->artisanCommand('route:list') }}`). Use `{{ $assist->artisanCommand('list') }}` to discover available commands and `{{ $assist->artisanCommand('[command] --help') }}` to check parameters.
- Inspect routes with `{{ $assist->artisanCommand('route:list') }}`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `{{ $assist->artisanCommand('config:show app.name') }}`, `{{ $assist->artisanCommand('config:show database.default') }}`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker
- Execute PHP in app context for debugging and testing code. Do not create models without user approval — prefer tests with factories. Prefer existing Artisan commands over custom tinker code.
- **Always use single quotes** to prevent shell expansion: `{{ $assist->artisanCommand("tinker --execute 'Your::code();'") }}`
  - Double quotes for PHP strings inside: `{{ $assist->artisanCommand("tinker --execute 'User::where(\"active\", true)->count();'") }}`
