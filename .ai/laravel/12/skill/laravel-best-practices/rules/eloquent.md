---
title: Eloquent Best Practices
impact: HIGH
tags: eloquent, relationships, scopes, observers, casts
---

# Eloquent Best Practices

## Use Correct Relationship Types

Use `hasMany`, `belongsTo`, `morphMany`, etc. with proper return type hints.

```php
public function comments(): HasMany
{
    return $this->hasMany(Comment::class);
}

public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}
```

## Use Local Scopes for Reusable Queries

Extract reusable query constraints into local scopes to avoid duplication.

Incorrect:
```php
$active = User::where('verified', true)->whereNotNull('activated_at')->get();
$articles = Article::whereHas('user', function ($q) {
    $q->where('verified', true)->whereNotNull('activated_at');
})->get();
```

Correct:
```php
public function scopeActive(Builder $query): Builder
{
    return $query->where('verified', true)->whereNotNull('activated_at');
}

// Usage
$active = User::active()->get();
$articles = Article::whereHas('user', fn ($q) => $q->active())->get();
```

## Apply Global Scopes Sparingly

Global scopes silently modify every query on the model, making debugging difficult. Prefer local scopes and reserve global scopes for truly universal constraints like soft deletes or multi-tenancy.

Incorrect (global scope for a conditional filter):
```php
class PublishedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('published', true);
    }
}
// Now admin panels, reports, and background jobs all silently skip drafts
```

Correct (local scope you opt into):
```php
public function scopePublished(Builder $query): Builder
{
    return $query->where('published', true);
}

Post::published()->paginate(); // Explicit
Post::paginate(); // Admin sees all
```

## Use Observers for Lifecycle Events

When the same model event logic appears in multiple places, consolidate it into an observer.

Incorrect (event logic scattered in controllers):
```php
public function store(StorePostRequest $request): RedirectResponse
{
    $post = Post::create($request->validated());
    $post->update(['slug' => Str::slug($post->title)]);
    Cache::forget('posts.recent');
    return redirect()->route('posts.show', $post);
}
```

Correct (observer handles cross-cutting events):
```php
class PostObserver
{
    public function saving(Post $post): void
    {
        $post->slug = Str::slug($post->title);
    }

    public function saved(Post $post): void
    {
        Cache::forget('posts.recent');
    }
}
```

## Define Attribute Casts

Use the `casts()` method (or `$casts` property following project convention) for automatic type conversion.

```php
protected function casts(): array
{
    return [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'total' => 'decimal:2',
    ];
}
```

## Cast Date Columns Properly

Always cast date columns. Use Carbon instances in templates instead of formatting strings manually.

Incorrect:
```blade
{{ Carbon::createFromFormat('Y-d-m H-i', $order->ordered_at)->toDateString() }}
```

Correct:
```php
protected function casts(): array
{
    return [
        'ordered_at' => 'datetime',
    ];
}
```

```blade
{{ $order->ordered_at->toDateString() }}
{{ $order->ordered_at->format('m-d') }}
```

## Use `whereBelongsTo()` for Relationship Queries

Cleaner than manually specifying foreign keys.

Incorrect:
```php
Post::where('user_id', $user->id)->get();
```

Correct:
```php
Post::whereBelongsTo($user)->get();
Post::whereBelongsTo($user, 'author')->get();
```
