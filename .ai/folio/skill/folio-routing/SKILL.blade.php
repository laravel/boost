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

## Verification

1. Run `{{ $assist->artisanCommand('folio:list') }}` to verify route registration
2. Test page loads at expected URL

## Common Pitfalls

- Forgetting to add named routes to new Folio pages
- Not following existing naming conventions when creating pages
- Creating routes manually in `routes/web.php` instead of using Folio's file-based routing
