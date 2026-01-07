@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Laravel Folio

- Laravel Folio is a file-based router. With Laravel Folio, a new route is created for every Blade file within the configured Folio directory. For example, pages are usually in `resources/views/pages/` and the file structure determines routes:
    - `pages/index.blade.php` → `/`
    - `pages/profile/index.blade.php` → `/profile`
    - `pages/auth/login.blade.php` → `/auth/login`
- You may list available Folio routes using `{{ $assist->artisanCommand('folio:list') }}` or using the `list-routes` tool.

### New Pages & Routes
- Always create new `folio` pages and routes using `{{ $assist->artisanCommand('folio:page [name]') }}` following existing naming conventions.

<code-snippet name="Example folio:page Commands for Automatic Routing" lang="shell">
    // Creates: resources/views/pages/products.blade.php → /products
    {!! $assist->artisanCommand('folio:page "products"') !!}

    // Creates: resources/views/pages/products/[id].blade.php → /products/{id}
    {!! $assist->artisanCommand('folio:page "products/[id]"') !!}
</code-snippet>

- Add a 'name' to each new Folio page at the very top of the file so it has a named route available for other parts of the codebase to use.
@verbatim
<code-snippet name="Adding Named Route to Folio Page" lang="php">
use function Laravel\Folio\name;

name('products.index');
</code-snippet>
@endverbatim

### Support & Documentation
- Folio supports: middleware, serving pages from multiple paths, subdomain routing, named routes, nested routes, index routes, route parameters, and route model binding.
- If available, use the `search-docs` tool to use Folio to its full potential and help the user effectively.
@verbatim
<code-snippet name="Folio Middleware Example" lang="php">
use function Laravel\Folio\{name, middleware};

name('admin.products');
middleware(['auth', 'verified', 'can:manage-products']);
?>
</code-snippet>
@endverbatim
