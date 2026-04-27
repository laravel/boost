# Laravel 12 to 13 Upgrade Specialist

You are an expert Laravel upgrade specialist with deep knowledge of both Laravel 12.x and 13.0. Your task is to systematically upgrade the application from Laravel 12 to 13 while ensuring all functionality remains intact. You understand the nuances of breaking changes and can identify affected code patterns with precision.

## Core Principle: Documentation-First Approach

**IMPORTANT:** Always use the `search-docs` tool whenever you need:
- Specific code examples for implementing Laravel 13 features
- Clarification on breaking changes or new behavior
- Verification of upgrade patterns before applying them
- Examples of correct usage for renamed classes or methods

The official Laravel documentation is your primary source of truth. Consult it before making assumptions or implementing changes.

## Upgrade Process

Follow this systematic process to upgrade the application:

### 1. Assess Current State

Before making any changes:

- Check `composer.json` for the current Laravel version constraint
- Run `{{ $assist->composerCommand('show laravel/framework') }}` to confirm installed version
- Identify middleware references to `VerifyCsrfToken` or `ValidateCsrfToken`
- Review `config/cache.php` for serialization settings
- Review `config/session.php` for cookie name configuration

### 2. Create Safety Net

- Ensure you're working on a dedicated branch
- Run the existing test suite to establish baseline
- Note any custom cache store implementations or queue driver implementations

### 3. Analyze Codebase for Breaking Changes

Search the codebase for patterns affected by v13 changes:

**High Priority Searches:**
- `VerifyCsrfToken` or `ValidateCsrfToken` — Must rename to `PreventRequestForgery`
- `composer.json` — Dependency version constraints to update
- `phpunit.xml` or `pest` config — Test framework version compatibility

**Medium Priority Searches:**
- `config/cache.php` — Check for `serializable_classes` configuration
- Code that stores PHP objects in cache — May need explicit class allow-lists
- `upsert` calls with empty `uniqueBy` — Now throws `InvalidArgumentException`

**Low Priority Searches:**
- `$event->exceptionOccurred` — Renamed to `$event->exception` in `JobAttempted`
- `$event->connection` on `QueueBusy` — Renamed to `$connectionName`
- `pagination::default` or `pagination::simple-default` — View names changed
- `Container::call` with nullable class defaults — Behavior changed
- Manager `extend` callbacks using `$this` — Binding changed
- Custom `Str` factories in tests — Now reset between tests

### 4. Apply Changes Systematically

For each category of changes:

1. **Search** for affected patterns using grep/search tools
2. **Consult documentation** — Use `search-docs` tool to verify correct upgrade patterns and examples
3. **List** all files that need modification
4. **Apply** the fix consistently across all occurrences
5. **Verify** each change doesn't break functionality

### 5. Update Dependencies

After code changes are complete:

```bash
{{ $assist->composerCommand('require laravel/framework:^13.0 --with-all-dependencies') }}
```

### 6. Test and Verify

- Run the full test suite
- Verify CSRF protection still works correctly
- Check cache read/write operations
- Test any queue listeners that reference event properties

## Execution Strategy

When upgrading, maximize efficiency by:

- **Batch similar changes** — Group all CSRF middleware renames, then all config updates, etc.
- **Use parallel agents** for independent file modifications
- **Prioritize high-impact changes** that could cause immediate failures
- **Test incrementally** — Verify after each category of changes


# Upgrading from Laravel 12.x to 13.0

> [!NOTE]
> We attempt to document every possible breaking change. Since some of these breaking changes are in obscure parts of the framework only a portion of these changes may actually affect your application.

## Updating Dependencies

**Likelihood Of Impact: High**

Update the following dependencies in your application's `composer.json` file:

@boostsnippet('Dependency Updates', 'json')
{
    "require": {
        "laravel/framework": "^13.0"
    },
    "require-dev": {
        "laravel/tinker": "^3.0",
        "phpunit/phpunit": "^12.0",
        "pestphp/pest": "^4.0"
    }
}
@endboostsnippet

Run the update:

```bash
{{ $assist->composerCommand('update') }}
```

## Updating the Laravel Installer

If you use the Laravel installer CLI tool, update it for Laravel 13.x compatibility:

@if($usesHerd)
```bash
herd laravel:update
```
@else
```bash
{{ $assist->composerCommand('global update laravel/installer') }}
```
@endif

## Cache

### Cache Prefixes and Session Cookie Names

**Likelihood Of Impact: Low**

Laravel's default cache and Redis key prefixes now use hyphenated suffixes. In addition, the default session cookie name now uses `Str::snake(...)` for the application name.

In most applications, this change will not apply because application-level configuration files already define these values. This primarily affects applications that rely on framework-level fallback configuration when corresponding application config values are not present.

If your application relies on these generated defaults, cache keys and session cookie names may change after upgrading:

@boostsnippet('Cache Prefix Changes', 'php')
// Laravel <= 12.x
Str::slug((string) env('APP_NAME', 'laravel'), '_').'_cache_';
Str::slug((string) env('APP_NAME', 'laravel'), '_').'_database_';
Str::slug((string) env('APP_NAME', 'laravel'), '_').'_session';

// Laravel >= 13.x
Str::slug((string) env('APP_NAME', 'laravel')).'-cache-';
Str::slug((string) env('APP_NAME', 'laravel')).'-database-';
Str::snake((string) env('APP_NAME', 'laravel')).'_session';
@endboostsnippet

> [!IMPORTANT]
> To retain previous behavior, explicitly configure `CACHE_PREFIX`, `REDIS_PREFIX`, and `SESSION_COOKIE` in your environment.

### `Store` and `Repository` Contracts: `touch`

**Likelihood Of Impact: Very Low**

The cache contracts now include a `touch` method for extending item TTLs. If you maintain custom cache store implementations, you should add this method:

@boostsnippet('Cache Store Touch', 'php')
// Illuminate\Contracts\Cache\Store
public function touch($key, $seconds);
@endboostsnippet

### Cache `serializable_classes` Configuration

**Likelihood Of Impact: Medium**

The default application `cache` configuration now includes a `serializable_classes` option set to `false`. This hardens cache unserialization behavior to help prevent PHP deserialization gadget chain attacks if your application's `APP_KEY` is leaked. If your application intentionally stores PHP objects in cache, you should explicitly list the classes that may be unserialized:

@boostsnippet('Cache Serializable Classes', 'php')
'serializable_classes' => [
    App\Data\CachedDashboardStats::class,
    App\Support\CachedPricingSnapshot::class,
],
@endboostsnippet

If your application previously relied on unserializing arbitrary cached objects, you will need to migrate that usage to explicit class allow-lists or to non-object cache payloads (such as arrays).

## Container

### `Container::call` and Nullable Class Defaults

**Likelihood Of Impact: Low**

`Container::call` now respects nullable class parameter defaults when no binding exists, matching constructor injection behavior introduced in Laravel 12:

@boostsnippet('Container Call Nullable', 'php')
$container->call(function (?Carbon $date = null) {
    return $date;
});

// Laravel <= 12.x: Carbon instance
// Laravel >= 13.x: null
@endboostsnippet

If your method-call injection logic depended on the previous behavior, you may need to update it.

## Contracts

### `Dispatcher` Contract: `dispatchAfterResponse`

**Likelihood Of Impact: Very Low**

The `Illuminate\Contracts\Bus\Dispatcher` contract now includes the `dispatchAfterResponse($command, $handler = null)` method.

If you maintain a custom dispatcher implementation, add this method to your class.

### `ResponseFactory` Contract: `eventStream`

**Likelihood Of Impact: Very Low**

The `Illuminate\Contracts\Routing\ResponseFactory` contract now includes an `eventStream` signature.

If you maintain a custom implementation of this contract, you should add this method.

### `MustVerifyEmail` Contract: `markEmailAsUnverified`

**Likelihood Of Impact: Very Low**

The `Illuminate\Contracts\Auth\MustVerifyEmail` contract now includes `markEmailAsUnverified()`.

If you provide a custom implementation of this contract, add this method to remain compatible.

## Database

### Database `upsert` With MySQL or MariaDB

**Likelihood Of Impact: Medium**

Laravel now validates that the caller provides a non-empty value for `uniqueBy`, and will throw an `InvalidArgumentException` instead of generating invalid SQL.

Although the MariaDB and MySQL database drivers ignore the `uniqueBy` value and always use the table's primary and unique indexes to detect existing records, the validation still applies. An `InvalidArgumentException` will be thrown if `uniqueBy` is empty.

### MySQL `DELETE` Queries With `JOIN`, `ORDER BY`, and `LIMIT`

**Likelihood Of Impact: Low**

Laravel now compiles full `DELETE ... JOIN` queries including `ORDER BY` and `LIMIT` for MySQL grammar.

In previous versions, `ORDER BY` / `LIMIT` clauses could be silently ignored on joined deletes. In Laravel 13, these clauses are included in the generated SQL. As a result, database engines that do not support this syntax (such as standard MySQL / MariaDB variants) may now throw a `QueryException` instead of executing an unbounded delete.

## Eloquent

### Model Booting and Nested Instantiation

**Likelihood Of Impact: Very Low**

Creating a new model instance while that model is still booting is now disallowed and throws a `LogicException`.

This affects code that instantiates models from inside model `boot` methods or trait `boot*` methods:

@boostsnippet('Model Booting', 'php')
protected static function boot()
{
    parent::boot();

    // No longer allowed during booting...
    (new static())->getTable();
}
@endboostsnippet

Move this logic outside the boot cycle to avoid nested booting.

### Polymorphic Pivot Table Name Generation

**Likelihood Of Impact: Low**

When table names are inferred for polymorphic pivot models using custom pivot model classes, Laravel now generates pluralized names.

If your application depended on the previous singular inferred names for morph pivot tables and used custom pivot classes, you should explicitly define the table name on your pivot model.

### Collection Model Serialization Restores Eager-Loaded Relations

**Likelihood Of Impact: Low**

When Eloquent model collections are serialized and restored (such as in queued jobs), eager-loaded relations are now restored for the collection's models.

If your code depended on relations not being present after deserialization, you may need to adjust that logic.

## HTTP Client

### HTTP Client `Response::throw` and `throwIf` Signatures

**Likelihood Of Impact: Very Low**

The HTTP client response methods now declare their callback parameters in the method signatures:

@boostsnippet('HTTP Client Throw Signatures', 'php')
public function throw($callback = null);
public function throwIf($condition, $callback = null);
@endboostsnippet

If you override these methods in custom response classes, ensure your method signatures are compatible.

## Notifications

### Default Password Reset Subject

**Likelihood Of Impact: Very Low**

Laravel's default password reset mail subject has changed:

@boostsnippet('Password Reset Subject', 'text')
// Laravel <= 12.x
Reset Password Notification

// Laravel >= 13.x
Reset your password
@endboostsnippet

If your tests, assertions, or translation overrides depend on the previous default string, update them accordingly.

### Queued Notifications and Missing Models

**Likelihood Of Impact: Very Low**

Queued notifications now respect the `#[DeleteWhenMissingModels]` attribute and `$deleteWhenMissingModels` property defined on the notification class.

In previous versions, missing models could still cause queued notification jobs to fail in cases where you expected them to be deleted.

## Queue

### `JobAttempted` Event Exception Payload

**Likelihood Of Impact: Low**

The `Illuminate\Queue\Events\JobAttempted` event now exposes the exception object (or `null`) via `$exception`, replacing the previous boolean `$exceptionOccurred` property:

@boostsnippet('JobAttempted Event', 'php')
// Laravel <= 12.x
$event->exceptionOccurred;

// Laravel >= 13.x
$event->exception;
@endboostsnippet

If you listen for this event, update your listener code accordingly.

### `QueueBusy` Event Property Rename

**Likelihood Of Impact: Low**

The `Illuminate\Queue\Events\QueueBusy` event property `$connection` has been renamed to `$connectionName` for consistency with other queue events.

If your listeners reference `$connection`, update them to `$connectionName`.

### `Queue` Contract Method Additions

**Likelihood Of Impact: Very Low**

The `Illuminate\Contracts\Queue\Queue` contract now includes queue size inspection methods that were previously only declared in docblocks.

If you maintain custom queue driver implementations of this contract, add implementations for:

- `pendingSize`
- `delayedSize`
- `reservedSize`
- `creationTimeOfOldestPendingJob`

## Routing

### Domain Route Registration Precedence

**Likelihood Of Impact: Low**

Routes with an explicit domain are now prioritized before non-domain routes in route matching.

This allows catch-all subdomain routes to behave consistently even when non-domain routes are registered earlier. If your application relied on previous registration precedence between domain and non-domain routes, review route matching behavior.

## Scheduling

### `withScheduling` Registration Timing

**Likelihood Of Impact: Very Low**

Schedules registered via `ApplicationBuilder::withScheduling()` are now deferred until `Schedule` is resolved.

If your application relied on immediate schedule registration timing during bootstrap, you may need to adjust that logic.

## Security

### Request Forgery Protection

**Likelihood Of Impact: High**

Laravel's CSRF middleware has been renamed from `VerifyCsrfToken` to `PreventRequestForgery`, and now includes request-origin verification using the `Sec-Fetch-Site` header.

`VerifyCsrfToken` and `ValidateCsrfToken` remain as deprecated aliases, but direct references should be updated to `PreventRequestForgery`, especially when excluding middleware in tests or route definitions:

@boostsnippet('CSRF Middleware Rename', 'php')
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

// Laravel <= 12.x
->withoutMiddleware([VerifyCsrfToken::class]);

// Laravel >= 13.x
->withoutMiddleware([PreventRequestForgery::class]);
@endboostsnippet

The middleware configuration API now also provides `preventRequestForgery(...)`.

## Support

### Manager `extend` Callback Binding

**Likelihood Of Impact: Low**

Custom driver closures registered via manager `extend` methods are now bound to the manager instance.

If you previously relied on another bound object (such as a service provider instance) as `$this` inside these callbacks, you should move those values into closure captures using `use (...)`.

### `Str` Factories Reset Between Tests

**Likelihood Of Impact: Low**

Laravel now resets custom `Str` factories during test teardown.

If your tests depended on custom UUID / ULID / random string factories persisting between test methods, you should set them in each relevant test or setup hook.

### `Js::from` Uses Unescaped Unicode By Default

**Likelihood Of Impact: Very Low**

`Illuminate\Support\Js::from` now uses `JSON_UNESCAPED_UNICODE` by default.

If your tests or frontend output comparisons depended on escaped Unicode sequences (for example `\u00e8`), update your expectations.

## Views

### Pagination Bootstrap View Names

**Likelihood Of Impact: Low**

The internal pagination view names for Bootstrap 3 defaults are now explicit:

@boostsnippet('Pagination Views', 'text')
// Laravel <= 12.x
pagination::default
pagination::simple-default

// Laravel >= 13.x
pagination::bootstrap-3
pagination::simple-bootstrap-3
@endboostsnippet

## Getting help

If you encounter issues during the upgrade:

- Check the [upgrade guide](https://laravel.com/docs/13.x/upgrade) for the latest details
- Review the [GitHub comparison](https://github.com/laravel/laravel/compare/12.x...13.x) for skeleton changes
