@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Do Things the Laravel Way

- Use `{{ $assist->artisanCommand('make:') }}` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `{{ $assist->artisanCommand('list') }}` and check their parameters with `{{ $assist->artisanCommand('[command] --help') }}`.
- If you're creating a generic PHP class, use `{{ $assist->artisanCommand('make:class') }}`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

## Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `{{ $assist->nodePackageManagerCommand('run build') }}` or ask the user to run `{{ $assist->nodePackageManagerCommand('run dev') }}` or `{{ $assist->composerCommand('run dev') }}`.
