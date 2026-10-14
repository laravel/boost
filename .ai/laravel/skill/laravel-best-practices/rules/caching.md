# Caching Best Practices

## `Cache::remember()` for Cache-Aside

Use `remember()` instead of `get()` + `if (! $val)` + `put()`; the manual check treats valid falsy values (`false`, `0`) as misses. `remember()` does not stop concurrent requests computing the same missing value; use an atomic lock when that matters.

## `Cache::flexible()` for Stale-While-Revalidate

`Cache::flexible('users', [300, 600], fn () => ...)` is fresh for 5 minutes and may be served stale until 10. The refresh is deferred (normally after the response), not a durable job. Once the stale window ends, the request recomputes synchronously.

## `Cache::memo()` vs `once()`

`Cache::memo()->get('settings')` keeps resolved values in memory for the current request or job, avoiding repeat store lookups; writes through it update its memory.

`once(fn () => ...)` memoizes a callback result per execution (scoped to the object instance) and never touches a cache store. Use `once()` for repeated computation, `memo()` for repeated store reads.

## Tags

`Cache::tags([...])->flush()` invalidates related entries without tracking keys. Unsupported by the `file`, `dynamodb`, and `database` drivers; confirm the store first.

## `Cache::add()` for Atomic Conditional Writes

Use `Cache::add()` instead of `has()` then `put()`, which races. When lock ownership and safe release matter, use `Cache::lock()`.

## Failover Store

The `failover` driver tries the next store only when an operation throws, not on a cache miss, and does not replicate data between stores. Configure it in production for resilience, e.g. `['redis', 'database']`.
