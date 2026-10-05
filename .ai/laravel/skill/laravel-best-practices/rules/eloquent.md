# Eloquent Best Practices

## Declare Precise Relationship Types

Use the relationship that matches the database association and declare its concrete return type (`HasMany`, `BelongsTo`).

## Use Local Scopes for Reusable Queries

Extract repeated constraints into local scopes with the `#[Scope]` attribute on a protected method, and reuse them in `whereHas('user', fn ($q) => $q->active())`.

```php
#[Scope]
protected function active(Builder $query): Builder
{
    return $query->where('verified', true)->whereNotNull('activated_at');
}
```

## Apply Global Scopes Sparingly

Global scopes silently alter every query, including admin panels, reports, and jobs, which makes debugging hard. Prefer local scopes; reserve global scopes for universal constraints like soft deletes or multi-tenancy.

## Define Attribute Casts

Use `casts()` (or `$casts` if the project does) for booleans, arrays, decimals, and similar conversions. Cast dates as `datetime` so code gets Carbon instances instead of calling `Carbon::parse()`. `created_at` and `updated_at` are already cast.

## Use `whereBelongsTo()`

Prefer `Post::whereBelongsTo($user)` over `where('user_id', $user->id)`. Pass the relationship name for non-conventional ones: `whereBelongsTo($user, 'author')`.

## Keep Application Queries Model-Aware

Prefer models over `DB::table()`, manual joins, and raw SQL for model-backed queries; models preserve casts, scopes, and table configuration. Use lower-level queries when their behavior is intended, such as complex joins, and cover them with tests. Use `(new User)->getTable()` when a builder query should follow the configured table name.

In migrations, use explicit table names rather than models, since models and scopes change after a migration ships.
