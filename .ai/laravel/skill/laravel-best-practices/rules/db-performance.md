# Database Performance Best Practices

## Eager Load Before Iterating

Use `with()` when a relationship is accessed for many models, to avoid N+1 queries. Lazy loading is fine for a single model or a relationship that may not be needed.

When constraining eager loads or selecting columns, keep every key Eloquent needs for matching: the parent's local key and the related foreign key (`posts.user_id`), plus the related primary key.

```php
Post::select('id', 'title', 'user_id')->with(['author:id,name'])->get();
```

## Prevent Lazy Loading in Development

Call `Model::preventLazyLoading(! app()->isProduction())` in `AppServiceProvider::boot()` so unloaded relationship access throws `LazyLoadingViolationException`. Customize with `handleLazyLoadingViolationUsing()`.

## Select Only Needed Columns

Narrow `select()` only when skipping large text, binary, or JSON columns gives a real benefit. Keep the matching keys described above.

## Process Large Data Sets Incrementally

Use `chunk()` or lazy iteration when loading the full result would exceed the memory budget; avoid `all()` followed by a loop.

Use `chunkById()` or `lazyById()` when the loop updates or deletes rows that affect query membership; `chunk()` uses offset pagination and skips rows as positions shift.

`cursor()` hydrates one model at a time but some drivers still buffer raw results, and it cannot eager load. Use `lazy()` when relationships must be eager loaded in chunks.

## Index Measured Query Patterns

Index for frequent, performance-sensitive queries. Appearing in `WHERE`, `ORDER BY`, `JOIN`, or `GROUP BY` does not alone justify an index; consider selectivity, write cost, and existing indexes. For `WHERE status = ? ORDER BY created_at`, use `$table->index(['status', 'created_at'])`.

Verify composite column order with production-like data and the query plan. Foreign keys may already create an index, so check before adding another.

## Count Relationships Without Loading Them

Use `withCount('comments')` and read `comments_count` instead of loading and counting models. Alias for conditional counts: `'comments as approved_comments_count' => fn ($q) => $q->where('approved', true)`.

## Keep Queries Out of Blade

Prepare data (with eager loads) in a controller, query class, or view composer so queries stay visible and testable. Avoid `@foreach (User::all() ...)` in templates.
