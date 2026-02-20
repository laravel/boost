---
name: folio-routing
description: "Creates file-based routes with Laravel Folio. Activates when creating new pages, setting up routes, working with route parameters or model binding, adding middleware to pages, working with resources/views/pages; or when the user mentions Folio, pages, file-based routing, page routes, or creating a new page for a URL path."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Folio Routing

## When to Apply

Activate this skill when:

- Creating pages with file-based routing
- Working with route parameters and model binding
- Adding middleware to Folio pages

## Documentation

Use `search-docs` for detailed Folio patterns and documentation.

## Basic Usage

Laravel Folio is a file-based router that creates a new route for every Blade file within the configured directory.

Pages are usually in `resources/views/pages/` and the file structure determines routes:

- `pages/index.blade.php` → `/`
- `pages/profile/index.blade.php` → `/profile`
- `pages/auth/login.blade.php` → `/auth/login`

### Listing Routes

You may list available Folio routes using `{{ $assist->artisanCommand('folio:list') }}` or using the `list-routes` tool.

### Creating Pages

Always create new `folio` pages and routes using `{{ $assist->artisanCommand('folio:page [name]') }}` following existing naming conventions.

@boostsnippet("Example folio:page Commands for Automatic Routing", "shell")
// Creates: resources/views/pages/products.blade.php → /products
{{ $assist->artisanCommand('folio:page "products"') }}

// Creates: resources/views/pages/products/[id].blade.php → /products/{id}
{{ $assist->artisanCommand('folio:page "products/[id]"') }}

// Creates: resources/views/pages/users/[User].blade.php → /users/{user} (implicit model binding)
{{ $assist->artisanCommand('folio:page "users/[User]"') }}
@endboostsnippet

## Route Parameters vs. Model Binding

Use the correct filename token based on intent:

- `[id]` (lowercase) captures a plain route parameter string
- `[User]` (capitalized model class) enables implicit Eloquent model binding
- `[Post:slug]` binds by a custom key instead of `id`

Model binding is case-sensitive in the filename. Avoid `[user]` when you expect a `User` model instance.

@boostsnippet("Route Parameter vs Model Binding Example", "blade")
{{-- pages/users/[id].blade.php --}}
<div>User ID: {{ $id }}</div>

{{-- pages/users/[User].blade.php --}}
<div>User ID: {{ $user->id }}</div>
@endboostsnippet

## Named Routes

Add a `name` at the top of each new Folio page to create a named route that other parts of the codebase can reference.

@boostsnippet("Named Routes Example", "php")
use function Laravel\Folio\name;

name('products.index');
@endboostsnippet

## Middleware

@boostsnippet("Middleware Example", "php")
use function Laravel\Folio\{name, middleware};

name('admin.products');
middleware(['auth', 'verified']);
@endboostsnippet

## Page Content Patterns

Folio pages are normal Blade files. Include practical data-loading code when creating or editing pages.

@boostsnippet("Inline Query Example in a Folio Page", "blade")
@php
use App\Models\Post;

$posts = Post::query()
    ->whereNotNull('published_at')
    ->latest('published_at')
    ->get();
@endphp

<ul>
    @foreach ($posts as $post)
        <li>{{ $post->title }}</li>
    @endforeach
</ul>
@endboostsnippet

@boostsnippet("Render Hook Example for View Data", "php")
<?php
use App\Models\Post;
use Illuminate\View\View;
use function Laravel\Folio\render;

render(function (View $view, Post $post) {
    return $view->with('photos', $post->author->photos);
});
?>
@endboostsnippet

## Verification

1. Run `{{ $assist->artisanCommand('folio:list') }}` to verify route registration
2. Test page loads at expected URL

## Common Pitfalls

- Forgetting to add named routes to new Folio pages
- Using `[id]` or `[user]` when model binding requires `[User]`
- Not following existing naming conventions when creating pages
- Creating routes manually in `routes/web.php` instead of using Folio's file-based routing

### Folio 404 Debug Checklist

1. Run `{{ $assist->artisanCommand('folio:list') }}`
2. If routes are missing, confirm Folio is mounted (`folio:install` + provider registration, or `Folio::path(...)`) and pages are under the mounted path
3. Verify filename-to-route mapping (`index.blade.php`, nested paths, `[id]` vs `[Model]`), then rerun `{{ $assist->artisanCommand('folio:list') }}`
