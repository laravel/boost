# Collection Best Practices

## Higher-Order Messages

`$users->each->markAsVip()` works for `each`, `map`, `filter`, `sum` and similar. Use an explicit closure when arguments or nontrivial logic would be clearer.

## Choose Between `cursor()` and `lazy()`

`cursor()` runs one query and hydrates models one at a time, but cannot eager load relationships, and driver result buffering can still use substantial memory. Use it for attribute-only iteration where one long-running query is acceptable.

`lazy()` runs chunked queries, returns a `LazyCollection`, supports `with()` eager loading per chunk, and holds no open cursor. Prefer it when you touch relationships.

## Use `lazyById()` When Updating While Iterating

`lazy()` paginates by offset, so updating columns that affect the query can skip or repeat rows. `lazyById()` paginates by a monotonic key and is safe for that case. Never change the key itself while iterating.

## Use `toQuery()` for Bulk Operations

`$users->toQuery()->update([...])` replaces a manual `whereIn('id', $users->modelKeys())`. It requires a non-empty collection of one model type, and like any bulk update it fires no per-model events.

## Custom Collection Classes

Declare them with `#[CollectedBy(UserCollection::class)]` on the model instead of overriding `newCollection()`.
