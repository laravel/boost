# Architecture Best Practices

## Extract Focused Business Operations

Extract a discrete operation into an action class when that makes it easier to reuse or test. Laravel gives action classes no special meaning; follow the project's naming and invocation conventions (typically a constructor-injected class with one `handle()` method).

## Inject Required Dependencies

Use constructor injection for dependencies needed throughout an object's lifetime, and method injection for those needed by one controller action, listener, or job handler. Avoid `app()` and `resolve()` inside methods when injection would make the dependency explicit.

## Depend on Contracts at Boundaries

Type-hint an interface (for example `PaymentGateway`, bound in a service provider) at external boundaries such as payment gateways, notification channels, and third-party services, only when testability or swappable implementations justify it. Internal classes with one implementation do not need an interface.

## Specify a Deterministic Sort Order

Without `ORDER BY`, row order is undefined, so `Post::paginate()` can repeat or skip rows across pages. Pick an order that fits the feature and add a unique tie-breaker (`orderByDesc('created_at')->orderByDesc('id')`) when stable pagination matters.

## Use Atomic Locks for Race Conditions

These solve different problems:

- `Cache::lock($key, 10)->block(5, fn () => ...)` serializes code across processes, and only works if the cache store supports locks.
- `lockForUpdate()` locks selected database rows and must run inside `DB::transaction()`.

## Use `mb_*` String Functions

When no Laravel helper exists, use `mb_strlen()` and `mb_strtolower()` for UTF-8 text. `strlen('José')` returns bytes (5), and `strtolower` ignores `Ü`. Prefer `Str::length()` and `Str::lower()` when available.

## Use `defer()` for Post-Response Work

For lightweight work that needs no retries or crash durability, `defer(fn () => ...)` runs the callback after the response is sent, in the same PHP process. Use a queued job when you need retries, queue controls, or durability.

## Use `Context` for Request-Scoped Data

`Context::add('tenant_id', ...)` (facade `Illuminate\Support\Facades\Context`, read with `Context::get('tenant_id')`; typically set in middleware) makes data available across the current execution without passing arguments through every layer. Visible context is added to log entries; visible and hidden context are both restored in queued jobs. Use `Context::addHidden()` to propagate to jobs without logging, and never put secrets in context unless that propagation is intended.

## Use `Concurrency::run()` for Parallel Execution

`Concurrency::run([fn () => ..., fn () => ...])` (facade `Illuminate\Support\Facades\Concurrency`) returns results in order, e.g. `[$users, $orders] = Concurrency::run([fn () => ..., fn () => ...])`. With a process-based driver each closure boots the application in a separate process, so use it only when independent queries, HTTP calls, or computations save more than the process and serialization overhead. The `sync` driver runs sequentially and is mainly for tests.

## Follow Framework Conventions

Override `$table`, `$primaryKey`, or relationship key arguments only when the domain or an existing schema requires it; conventional naming keeps models and relationships short.
