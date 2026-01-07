@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Laravel 11

- Use the `search-docs` tool to get version-specific documentation.
@if (file_exists(base_path('app/Http/Kernel.php')))
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel 11 file structure.
- This is **perfectly fine** and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the Laravel 11 structure unless the user explicitly requests it.

### Laravel 10 Structure
- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`
@else
- Laravel 11 brought a new streamlined file structure which this project now uses.

### Laravel 11 Structure
- In Laravel 11, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.
@endif

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### New Artisan Commands
- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `{{ $assist->artisanCommand('make:enum') }}`
    - `{{ $assist->artisanCommand('make:class') }}`
    - `{{ $assist->artisanCommand('make:interface') }}`
