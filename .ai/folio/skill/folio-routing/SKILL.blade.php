---
name: folio-routing
description: >-
  Creates file-based routes with Laravel Folio. Activates when creating new pages, setting up
  routes, working with route parameters or model binding, adding middleware to pages, working with
  resources/views/pages; or when the user mentions Folio, pages, file-based routing, page routes,
  or creating a new page for a URL path.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Folio Routing

## When to Apply

- Creating pages with file-based routing
- Working with route parameters and model binding
- Adding middleware to Folio pages

## Core Pattern

Laravel Folio is a file-based router. With Laravel Folio, a new route is created for every Blade file within the configured Folio directory.

Pages are usually in `resources/views/pages/` and the file structure determines routes:
- `pages/index.blade.php` → `/`
- `pages/profile/index.blade.php` → `/profile`
- `pages/auth/login.blade.php` → `/auth/login`

### Listing Routes

You may list available Folio routes using `{{ $assist->artisanCommand('folio:list') }}` or using the `list-routes` tool.

## Creating Pages

Always create new `folio` pages and routes using `{{ $assist->artisanCommand('folio:page [name]') }}` following existing naming conventions.

@verbatim
<code-snippet name="Example folio:page Commands for Automatic Routing" lang="shell">
// Creates: resources/views/pages/products.blade.php → /products
{!! $assist->artisanCommand('folio:page "products"') !!}

// Creates: resources/views/pages/products/[id].blade.php → /products/{id}
{!! $assist->artisanCommand('folio:page "products/[id]"') !!}
</code-snippet>
@endverbatim

## Named Routes

Add a 'name' to each new Folio page at the very top of the file so it has a named route available for other parts of the codebase to use.

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

## Verification

1. Run `{{ $assist->artisanCommand('folio:list') }}` to verify route registered
2. Test page loads at expected URL


## Documentation

use the `search-docs` tool to use Folio to its full potential and help the user effectively.

## Common Pitfalls

- Forgetting to add named routes to new Folio pages
- Not following existing naming conventions when creating pages
- Creating routes manually in `routes/web.php` instead of using Folio's file-based routing
