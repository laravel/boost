# Laravel 12
@if($assist->hasMcpEnabled())
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
@endif
@if (file_exists(app_path('Http/Kernel.php')))
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is perfectly fine and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the new Laravel structure unless the user explicitly requests it.

## Laravel 10 Structure
- Middleware typically lives in `{{ $assist->appPath('Http/Middleware/') }}` and service providers in `{{ $assist->appPath('Providers/') }}`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration happens in `{{ $assist->appPath('Http/Kernel.php') }}`
    - Exception handling is in `{{ $assist->appPath('Exceptions/Handler.php') }}`
    - Console commands and schedule register in `{{ $assist->appPath('Console/Kernel.php') }}`
    - Rate limits likely exist in `RouteServiceProvider` or `{{ $assist->appPath('Http/Kernel.php') }}`
@else
- This project uses the streamlined Laravel 11+ structure: register middleware, exceptions, and routing in `bootstrap/app.php` and service providers in `bootstrap/providers.php`. There is no `{{ $assist->appPath('Http/Kernel.php') }}` or `{{ $assist->appPath('Console/Kernel.php') }}`, and commands in `{{ $assist->appPath('Console/Commands/') }}` auto-register.
@endif

@scoped(['database/migrations/**'])
## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
@endscoped

@scoped(['database/migrations/**', 'app/Models/**'])
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.
@endscoped
