---
name: routing-with-wayfinder
description: Type-safe routing with Laravel Wayfinder. Use when generating route helpers, creating type-safe links, or improving route autocompletion.
---

# Routing with Wayfinder

## When to use this skill

Use this skill when the user asks about:
- Type-safe route generation
- Route autocompletion in IDE
- Generating route helpers
- Replacing route() helper with typed alternatives

## Installation

```bash
composer require laravel/wayfinder
php artisan wayfinder:generate
```

## Basic Usage

After generation, use typed route helpers:

```php
// Instead of
route('users.show', ['user' => $user]);

// Use
use App\Routes\Users;

Users::show($user);
```

## Generated Helpers

Wayfinder generates classes based on your routes:

```php
// routes/web.php
Route::resource('users', UserController::class);
Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('posts.comments.index');
```

Generated:
```php
// app/Routes/Users.php
namespace App\Routes;

class Users
{
    public static function index(): string
    {
        return route('users.index');
    }

    public static function create(): string
    {
        return route('users.create');
    }

    public static function store(): string
    {
        return route('users.store');
    }

    public static function show(User|int $user): string
    {
        return route('users.show', ['user' => $user]);
    }

    public static function edit(User|int $user): string
    {
        return route('users.edit', ['user' => $user]);
    }

    public static function update(User|int $user): string
    {
        return route('users.update', ['user' => $user]);
    }

    public static function destroy(User|int $user): string
    {
        return route('users.destroy', ['user' => $user]);
    }
}
```

## In Blade Templates

```blade
{{-- Before --}}
<a href="{{ route('users.show', $user) }}">View User</a>

{{-- After --}}
<a href="{{ \App\Routes\Users::show($user) }}">View User</a>
```

## With Inertia

```php
use App\Routes\Users;

return Inertia::render('Users/Show', [
    'user' => $user,
    'editUrl' => Users::edit($user),
    'deleteUrl' => Users::destroy($user),
]);
```

## In Controllers

```php
use App\Routes\Users;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $user = User::create($request->validated());

        return redirect(Users::show($user));
    }
}
```

## Configuration

Publish config:

```bash
php artisan vendor:publish --tag=wayfinder-config
```

```php
// config/wayfinder.php
return [
    'path' => app_path('Routes'),
    'namespace' => 'App\\Routes',
];
```

## Regeneration

Regenerate after route changes:

```bash
php artisan wayfinder:generate
```

Add to composer scripts:
```json
{
    "scripts": {
        "post-update-cmd": [
            "@php artisan wayfinder:generate"
        ]
    }
}
```

## Nested Resources

```php
// routes/web.php
Route::resource('users.posts', UserPostController::class);
```

Generated:
```php
namespace App\Routes\Users;

class Posts
{
    public static function index(User|int $user): string
    {
        return route('users.posts.index', ['user' => $user]);
    }

    public static function show(User|int $user, Post|int $post): string
    {
        return route('users.posts.show', [
            'user' => $user,
            'post' => $post,
        ]);
    }
}
```

Usage:
```php
use App\Routes\Users\Posts;

Posts::show($user, $post);
```

## API Routes

```php
// routes/api.php
Route::prefix('api')->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

Generated with API prefix handling:
```php
namespace App\Routes\Api;

class Products
{
    public static function index(): string
    {
        return route('api.products.index');
    }
}
```

## Query Parameters

For routes with query parameters:

```php
// Manual approach still needed for query params
Users::index() . '?' . http_build_query(['page' => 2, 'sort' => 'name']);
```

## IDE Benefits

- **Autocompletion** - Full IDE support for route names
- **Type checking** - Parameters are typed
- **Refactoring** - Rename routes safely
- **Find usages** - See where routes are used

## Best Practices

1. **Regenerate on changes** - Run after modifying routes
2. **Add to CI** - Verify routes are in sync
3. **Use consistently** - Replace all route() calls
4. **Type parameters** - Pass models when possible
5. **Version control** - Commit generated files
6. **IDE indexing** - Ensure Routes folder is indexed
