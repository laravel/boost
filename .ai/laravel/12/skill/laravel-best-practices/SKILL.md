---
name: laravel-best-practices
description: "Laravel framework best practices and performance optimization guidelines. Contains 125+ rules across 19 categories, prioritized by impact. Activates when writing, reviewing, or refactoring Laravel code — models, controllers, migrations, jobs, form requests, routes, services, HTTP client calls, mailables, events, blade views, caching, scheduling, or any PHP code in a Laravel project. Also activates when the user mentions best practices, code quality, performance, security, or asks for a code review. Use this skill proactively whenever generating Laravel code to ensure it follows established patterns — even for simple tasks like creating a model, adding a migration, or writing a controller."
license: MIT
metadata:
  author: laravel
---

# Laravel Best Practices

125+ rules across 19 categories, prioritized by impact. Each rule teaches *what* to do and *why* — for exact API syntax, verify with `search-docs` for the installed Laravel version.

## Important: Use `search-docs` for Syntax

These rules teach patterns and principles. For version-specific method signatures and usage examples, call `search-docs` before writing code. The rules tell you *what* exists; the docs tell you *how* to use it in your version.

## Rule Categories

| Priority | Category | Impact | File |
|----------|----------|--------|------|
| 1 | Database Performance | CRITICAL | `rules/db-performance.md` |
| 2 | Advanced Query Patterns | HIGH | `rules/advanced-queries.md` |
| 3 | Security | CRITICAL | `rules/security.md` |
| 4 | Caching | HIGH | `rules/caching.md` |
| 5 | Eloquent Patterns | HIGH | `rules/eloquent.md` |
| 6 | Validation & Forms | HIGH | `rules/validation.md` |
| 7 | Configuration | HIGH | `rules/config.md` |
| 8 | Testing Patterns | HIGH | `rules/testing.md` |
| 9 | Deployment | HIGH | `rules/deployment.md` |
| 10 | Queue & Job Patterns | MEDIUM | `rules/queue-jobs.md` |
| 11 | Routing & Controllers | MEDIUM | `rules/routing.md` |
| 12 | HTTP Client | MEDIUM | `rules/http-client.md` |
| 13 | Events & Notifications | MEDIUM | `rules/events-notifications.md` |
| 14 | Mail | MEDIUM | `rules/mail.md` |
| 15 | Error Handling | MEDIUM | `rules/error-handling.md` |
| 16 | Task Scheduling | MEDIUM | `rules/scheduling.md` |
| 17 | Architecture | MEDIUM | `rules/architecture.md` |
| 18 | Migrations | MEDIUM | `rules/migrations.md` |
| 19 | Collections | MEDIUM | `rules/collections.md` |
| 20 | Blade & Views | MEDIUM | `rules/blade-views.md` |
| 21 | Conventions & Style | LOW | `rules/style.md` |

## Quick Reference

### 1. Database Performance (CRITICAL)

- Always eager load relationships with `with()` to prevent N+1 queries
- Enable `Model::preventLazyLoading()` in development
- Select only needed columns instead of `SELECT *`
- Use `chunk()` or `chunkById()` for large datasets
- Add indexes on columns used in `WHERE`, `ORDER BY`, and `JOIN`
- Use `withCount()` instead of loading relations to count
- Use `cursor()` for memory-efficient read-only iteration
- Never execute queries in Blade templates

### 2. Advanced Query Patterns (HIGH)

- Use `addSelect()` subqueries instead of eager-loading entire has-many for a single value
- Create dynamic relationships via subquery FK + `belongsTo`
- Use conditional aggregates (`CASE WHEN` in `selectRaw`) instead of multiple count queries
- Use `setRelation()` to prevent circular N+1 queries
- Prefer `whereIn` + `pluck()` over `whereHas` for better index usage
- Sometimes two simple queries beat one complex query
- Use compound indexes matching `orderBy` column order
- Use correlated subqueries in `orderBy` for has-many sorting (avoid joins)

### 3. Security (CRITICAL)

- Always define `$fillable` or `$guarded` on models
- Authorize every action using policies or gates
- Use Eloquent or query builder, never raw SQL with user input
- Use `{{ }}` for escaping, avoid `{!! !!}` with user content
- Include `@csrf` in all POST/PUT/DELETE Blade forms
- Apply `throttle` middleware to auth and API routes
- Validate MIME type, extension, and size for file uploads
- Never commit `.env`, always use `config()` for secrets
- Run `composer audit` periodically
- Prefer `--readable` when creating encrypted env files
- Use `encrypted` cast for sensitive DB fields, mark as `hidden`

### 4. Caching (HIGH)

- Use `Cache::remember()` instead of manual get/put
- Use `Cache::flexible()` for stale-while-revalidate on high-traffic data
- Use `Cache::memo()` to avoid redundant cache hits within a request
- Use cache tags to invalidate related groups
- Use `Cache::add()` for atomic conditional writes
- Configure failover cache stores in production

### 5. Eloquent Patterns (HIGH)

- Use correct relationship types with return type hints
- Use local scopes for reusable query constraints
- Apply global scopes sparingly, document their existence
- Use observers for cross-cutting lifecycle events
- Define attribute casts in the `casts()` method
- Always cast date columns, use Carbon instances in templates
- Use `whereBelongsTo($model)` for cleaner queries

### 6. Validation & Forms (HIGH)

- Use Form Request classes, not inline validation
- Use `['required', 'email']` not `'required|email'`
- Always use `$request->validated()`, never `$request->all()`
- Create invokable Rule classes for complex reusable logic
- Use `Rule::when()` for conditional validation rules
- Use `after()` instead of `withValidator()`

### 7. Configuration (HIGH)

- Only use `env()` inside config files
- Use encrypted env or external secrets manager
- Use `App::environment()` or `app()->isProduction()`
- Use config, lang files, and constants instead of hardcoded text

### 8. Testing Patterns (HIGH)

- Use `LazilyRefreshDatabase` over `RefreshDatabase` for speed
- Use `assertModelExists()` over raw `assertDatabaseHas()`
- Use factory states and sequences instead of manual overrides
- Use `Exceptions::fake()` to assert exception reporting
- Call `Event::fake()` after factory setup, not before
- Use `recycle()` to share relationship instances across factories

### 9. Deployment (HIGH)

- Use Laravel Cloud for fully-managed, auto-scaling deployments
- Run `php artisan optimize` on every deploy (caches config, events, routes, views)
- Disable debug mode in production (`APP_DEBUG=false`)
- Restart queue workers and Horizon after deploy (`php artisan reload`)
- Use the `/up` health route for load balancers and uptime monitors

### 10. Queue & Job Patterns (MEDIUM)

- Set `retry_after` greater than job `timeout`
- Use exponential backoff arrays `[1, 5, 10]` for retries
- Implement `ShouldBeUnique` to prevent duplicates
- Always implement `failed()` method
- Use `RateLimited` middleware for external API calls
- Use `Bus::batch()` for related jobs
- When using `retryUntil()`, set `$tries = 0`
- Use `WithoutOverlapping::untilProcessing()` for concurrency control
- Use Horizon for complex multi-queue scenarios

### 11. Routing & Controllers (MEDIUM)

- Use implicit route model binding
- Use scoped bindings for nested resources
- Use `Route::resource()` or `apiResource()`
- Keep methods under 10 lines, extract to actions/services
- Type-hint Form Requests for auto-validation

### 12. HTTP Client (MEDIUM)

- Always set explicit `timeout` and `connectTimeout`
- Use `retry()` with exponential backoff for external APIs
- Always check response status or use `throw()`
- Use `Http::pool()` for concurrent independent requests
- Use `Http::fake()` and `preventStrayRequests()` in tests

### 13. Events & Notifications (MEDIUM)

- Rely on event discovery, not manual registration
- Run `event:cache` in production deploys
- Use `ShouldDispatchAfterCommit` inside transactions
- Always queue notifications with `ShouldQueue`
- Use `afterCommit()` on notifications in transactions
- Use on-demand notifications for non-user recipients
- Implement `HasLocalePreference` on notifiable models

### 14. Mail (MEDIUM)

- Implement `ShouldQueue` on mailable classes directly
- Use `afterCommit()` on mailables inside transactions
- Use `assertQueued()` not `assertSent()` for queued mailables
- Use Markdown mailables for transactional emails
- Separate content tests from sending tests

### 15. Error Handling (MEDIUM)

- Put `report()` and `render()` on the exception class itself
- Use `ShouldntReport` for exceptions that should never log
- Throttle high-volume exceptions to protect log sinks
- Enable `dontReportDuplicates()` for multi-catch scenarios
- Force JSON rendering for API routes
- Add structured context via `context()` on exception classes

### 16. Task Scheduling (MEDIUM)

- Use `withoutOverlapping()` on variable-duration tasks
- Use `onOneServer()` on multi-server deployments
- Use `runInBackground()` for concurrent long tasks
- Use `environments()` to restrict tasks to appropriate environments
- Use `takeUntilTimeout()` for time-bounded processing
- Use schedule groups for shared configuration

### 17. Architecture (MEDIUM)

- Use single-purpose Action classes for business operations
- Use dependency injection, avoid `app()` helper
- Code to interfaces for testability and flexibility
- Prefer official Laravel packages when available → `rules/first-party-packages.md`
- Default to `ORDER BY id DESC` or `created_at DESC`
- Use `Cache::lock()` or `lockForUpdate()` for race conditions
- Use `mb_*` string functions for UTF-8 safety
- Use S3 on Cloud/Vapor, local disk is ephemeral
- Follow Laravel conventions, don't override defaults

### 18. Migrations (MEDIUM)

- Always generate migrations with `php artisan make:migration`
- Use `constrained()` for foreign keys
- Never modify migrations that have run in production
- Add indexes in the migration, not as an afterthought
- Add `->index()` for FK columns (Postgres needs explicit indexes)
- Mirror column defaults in model `$attributes`
- Leave `down()` empty to prevent data loss
- One concern per migration, never mix DDL and DML

### 19. Collections (MEDIUM)

- Use higher-order messages for simple operations
- Choose `cursor()` vs `lazy()` correctly based on relationship needs
- Use `lazyById()` when updating records while iterating
- Use `toQuery()` for bulk operations on collections
- Use `#[CollectedBy]` for custom collection classes

### 20. Blade & Views (MEDIUM)

- Use `$attributes->merge()` in component templates
- Use `@pushOnce` for per-component scripts
- Prefer Blade components over `@include`
- Use View Composers for shared view data
- Use Blade fragments for partial re-renders (htmx/Turbo)
- Use `@aware` for deeply nested component props

### 21. Conventions & Style (LOW)

- Follow Laravel naming conventions for all entities
- Prefer Laravel helpers and shorter readable syntax
- Use `Str`, `Arr`, `Number`, `Uri` helpers over raw PHP functions
- Use fluent `Str::of()` for chainable string transformations
- Use `$request->string()` for fluent input handling
- Do not put JS/CSS in Blade or HTML in PHP classes
- Code should be readable; comments only for config files

## How to Use

**Use the quick reference above for everyday code generation.** It covers 125+ patterns — most tasks won't need deeper detail.

When you need code examples or a one-liner is ambiguous, **delegate rule file reads to a sub-agent** to keep the main context clean. Each category is a single file:

```
rules/db-performance.md        # Database Performance
rules/security.md              # Security
rules/eloquent.md              # Eloquent Patterns
rules/queue-jobs.md            # Queue & Job Patterns
rules/architecture.md          # Architecture
rules/migrations.md            # Migrations
```

**Context management:** Rule files contain verbose code examples (100–200+ lines each). Reading them directly bloats the main context. Spawn a sub-agent to read the relevant rule file(s), apply the patterns, and return only the result.

After identifying which patterns apply, use `search-docs` to verify exact API syntax for the user's installed Laravel version.
