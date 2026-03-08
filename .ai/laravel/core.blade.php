@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Do Things the Laravel Way

- Use `{{ $assist->artisanCommand('make:') }}` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `{{ $assist->artisanCommand('make:class') }}`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database
- Prefer Eloquent models and relationships over `DB::` facades or raw queries. Use `Model::query()` with eager loading to prevent N+1 problems.
- Use Laravel's query builder only for complex operations that cannot be expressed cleanly with Eloquent.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `{{ $assist->artisanCommand('make:model') }}`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Laravel Conventions
- Use gates and policies for authorization. Use Sanctum for API auth.
- Use named routes and `route()` for URL generation.
- Use `ShouldQueue` for long-running jobs.
- Never use `env()` outside config files; use `config()` instead.

## Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `{{ $assist->artisanCommand('make:test [options] {name}') }}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.
