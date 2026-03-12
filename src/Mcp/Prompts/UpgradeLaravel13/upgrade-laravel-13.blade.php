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

## Important Notes

- Estimated upgrade time is ~10 minutes — this is a lightweight release
- Most breaking changes are low-impact and may not affect your application
- Deprecated aliases (`VerifyCsrfToken`, `ValidateCsrfToken`) still work but should be migrated

---

# Upgrading from Laravel 12.x to 13.0

Laravel 13 is a lightweight release focused on security hardening, developer-experience improvements, and contract updates. Most applications can upgrade with minimal changes.

> [!tip] Quick upgrade
> Estimated upgrade time is approximately 10 minutes. Most of the breaking changes are low-impact and only affect applications using advanced features or customization.

## High-impact changes

These changes are most likely to affect your application and should be reviewed carefully.

### Updating Dependencies

Update the following dependencies in your application's `composer.json` file:

@boostsnippet('Dependency Updates', 'json')
{
    "require": {
        "laravel/framework": "^13.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^12.0",
        "pestphp/pest": "^4.0"
    }
}
@endboostsnippet

Run the update:

```bash
{{ $assist->composerCommand('update') }}
```

### Updating the Laravel Installer

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

### Request Forgery Protection

Laravel's CSRF middleware has been renamed from `VerifyCsrfToken` / `ValidateCsrfToken` to `PreventRequestForgery`, and now includes request-origin verification using the `Sec-Fetch-Site` header.

The old class names remain as deprecated aliases, but direct references should be updated:

@boostsnippet('CSRF Middleware Rename', 'php')
// Before (Laravel 12.x)
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

->withoutMiddleware([VerifyCsrfToken::class]);

// After (Laravel 13.x)
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

->withoutMiddleware([PreventRequestForgery::class]);
@endboostsnippet

The middleware configuration API now also provides `preventRequestForgery(...)`.

**Search for:** Any references to `VerifyCsrfToken` or `ValidateCsrfToken` in middleware exclusions, test helpers, route definitions, and service providers.

## Medium-impact changes

These changes may affect certain parts of your application depending on which features you use.

### Cache `serializable_classes` Configuration

The default application `cache` configuration now includes a `serializable_classes` option set to `false`. This hardens cache unserialization behavior to help prevent PHP deserialization gadget chain attacks if your application's `APP_KEY` is leaked.

If your application intentionally stores PHP objects in cache, you should explicitly list the classes that may be unserialized:

@boostsnippet('Cache Serializable Classes', 'php')
// config/cache.php
'serializable_classes' => [
    App\Data\CachedDashboardStats::class,
    App\Support\CachedPricingSnapshot::class,
],
@endboostsnippet

If your application previously relied on unserializing arbitrary cached objects, you will need to migrate that usage to explicit class allow-lists or to non-object cache payloads (such as arrays).

## Low-impact changes

These changes only affect applications using advanced features or customization.

### Cache Prefixes and Session Cookie Names

Laravel's default cache and Redis key prefixes now use hyphenated suffixes. The default session cookie name now uses `Str::snake(...)` for the application name:

@boostsnippet('Cache Prefix Changes', 'php')
// Laravel 12.x
Str::slug((string) env('APP_NAME', 'laravel'), '_').'_cache_';
Str::slug((string) env('APP_NAME', 'laravel'), '_').'_database_';
Str::slug((string) env('APP_NAME', 'laravel'), '_').'_session';

// Laravel 13.x
Str::slug((string) env('APP_NAME', 'laravel')).'-cache-';
Str::slug((string) env('APP_NAME', 'laravel')).'-database-';
Str::snake((string) env('APP_NAME', 'laravel')).'_session';
@endboostsnippet

To retain previous behavior, explicitly configure `CACHE_PREFIX`, `REDIS_PREFIX`, and `SESSION_COOKIE` in your environment.

### `Container::call` and Nullable Class Defaults

`Container::call` now respects nullable class parameter defaults when no binding exists, matching constructor injection behavior introduced in Laravel 12:

@boostsnippet('Container Call Nullable', 'php')
$container->call(function (?Carbon $date = null) {
    return $date;
});

// Laravel 12.x: Carbon instance
// Laravel 13.x: null
@endboostsnippet

If your method-call injection logic depended on receiving an instance for nullable parameters, update it accordingly.

### Domain Route Registration Precedence

Routes with an explicit domain are now prioritized before non-domain routes in route matching. This allows catch-all subdomain routes to behave consistently even when non-domain routes are registered earlier.

If your application relied on previous registration precedence between domain and non-domain routes, review route matching behavior.

### `JobAttempted` Event Exception Payload

The `JobAttempted` event now exposes the exception object (or `null`) via `$exception`, replacing the previous boolean `$exceptionOccurred` property:

@boostsnippet('JobAttempted Event', 'php')
// Laravel 12.x
$event->exceptionOccurred; // bool

// Laravel 13.x
$event->exception; // Throwable|null
@endboostsnippet

If you listen for this event, update your listener code accordingly.

### `QueueBusy` Event Property Rename

The `QueueBusy` event property `$connection` has been renamed to `$connectionName` for consistency with other queue events:

@boostsnippet('QueueBusy Event', 'php')
// Laravel 12.x
$event->connection;

// Laravel 13.x
$event->connectionName;
@endboostsnippet

### Manager `extend` Callback Binding

Custom driver closures registered via manager `extend` methods are now bound to the manager instance. If you previously relied on another bound object (such as a service provider instance) as `$this` inside these callbacks, move those values into closure captures:

@boostsnippet('Manager Extend Binding', 'php')
// Laravel 12.x — $this was bound to the service provider
$manager->extend('custom', function () {
    return $this->buildCustomDriver(); // $this = provider
});

// Laravel 13.x — $this is now the manager; capture dependencies explicitly
$provider = $this;
$manager->extend('custom', function () use ($provider) {
    return $provider->buildCustomDriver();
});
@endboostsnippet

### MySQL `DELETE` Queries With `JOIN`, `ORDER BY`, and `LIMIT`

Laravel now compiles full `DELETE ... JOIN` queries including `ORDER BY` and `LIMIT` for MySQL grammar. In previous versions, these clauses could be silently ignored on joined deletes. Database engines that do not support this syntax may now throw a `QueryException`.

### Pagination Bootstrap View Names

The internal pagination view names for Bootstrap 3 defaults have been renamed:

@boostsnippet('Pagination Views', 'php')
// Laravel 12.x
'pagination::default'
'pagination::simple-default'

// Laravel 13.x
'pagination::bootstrap-3'
'pagination::simple-bootstrap-3'
@endboostsnippet

If your application references these pagination view names directly, update those references.

### Polymorphic Pivot Table Name Generation

When table names are inferred for polymorphic pivot models using custom pivot model classes, Laravel now generates pluralized names. If your application depended on the previous singular inferred names, explicitly define the table name on your pivot model.

### Collection Model Serialization Restores Eager-Loaded Relations

When Eloquent model collections are serialized and restored (such as in queued jobs), eager-loaded relations are now restored for the collection's models. If your code depended on relations not being present after deserialization, you may need to adjust that logic.

### `Str` Factories Reset Between Tests

Laravel now resets custom `Str` factories during test teardown. If your tests depended on custom UUID / ULID / random string factories persisting between test methods, set them in each relevant test or setup hook.

### Model Booting and Nested Instantiation

Creating a new model instance while that model is still booting is now disallowed and throws a `LogicException`:

@boostsnippet('Model Booting', 'php')
protected static function boot()
{
    parent::boot();

    // No longer allowed during booting...
    (new static())->getTable();
}
@endboostsnippet

Move this logic outside the boot cycle to avoid nested booting.

## Very-low-impact contract and miscellaneous changes

These changes affect custom implementations of framework contracts or very specific edge cases:

- **Cache `Store` and `Repository` contracts**: New `touch` method for extending item TTLs. Add to custom cache store implementations.
- **`Dispatcher` contract**: New `dispatchAfterResponse($command, $handler = null)` method. Add to custom dispatcher implementations.
- **`ResponseFactory` contract**: New `eventStream` method signature. Add to custom response factory implementations.
- **`MustVerifyEmail` contract**: New `markEmailAsUnverified()` method. Add to custom implementations.
- **`Queue` contract**: New `pendingSize`, `delayedSize`, `reservedSize`, and `creationTimeOfOldestPendingJob` methods. Add to custom queue driver implementations.
- **HTTP Client `throw` / `throwIf` signatures**: Callback parameters are now declared in the method signatures. Ensure custom response class overrides are compatible.
- **Password reset subject**: Default changed from "Reset Password Notification" to "Reset your password". Update tests or translation overrides if needed.
- **Queued notifications**: Now respect `#[DeleteWhenMissingModels]` attribute on the notification class.
- **`withScheduling` registration timing**: Schedules via `ApplicationBuilder::withScheduling()` are now deferred until `Schedule` resolves.
- **`Js::from` uses `JSON_UNESCAPED_UNICODE`**: Update test expectations if they relied on escaped Unicode sequences.

## Getting help

If you encounter issues during the upgrade:

- Check the [upgrade guide](https://laravel.com/docs/13.x/upgrade) for the latest details
- Review the [GitHub comparison](https://github.com/laravel/laravel/compare/12.x...13.x) for skeleton changes
