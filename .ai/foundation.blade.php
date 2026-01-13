@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - {{ PHP_VERSION }}
@foreach (app(\Laravel\Roster\Roster::class)->packages()->unique(fn ($package) => $package->rawName()) as $package)
- {{ $package->rawName() }} ({{ $package->name() }}) - v{{ $package->majorVersion() }}
@endforeach

@if (! empty(config('boost.purpose')))
Application purpose: {!! config('boost.purpose') !!}

@endif
## Skills Activation

This project has domain-specific skills available. Activate the relevant skill when you need detailed patterns and guidance for that domain.

### Frontend Development
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::LIVEWIRE))
- **`building-livewire-components`** — Activate when creating, modifying, or testing Livewire components; debugging reactivity issues; or working with wire: directives.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_LARAVEL) || $assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE) || $assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_REACT) || $assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
- **`building-inertia-apps`** — Activate when working with Inertia pages, forms, navigation, deferred props, or any SPA-specific patterns.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::TAILWINDCSS))
- **`using-tailwindcss`** — Activate when adding styles, working on responsive design, implementing dark mode, or extracting component patterns.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::VOLT))
- **`using-volt-components`** — Activate when creating or modifying Volt single-file components (functional or class-based).
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::FOLIO))
- **`using-folio-routing`** — Activate when creating new pages, working with file-based routes, or adding middleware to Folio pages.
@endif

### Testing
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::PEST))
- **`testing-with-pest`** — Activate when writing new tests, debugging test failures, or learning Pest patterns like datasets and mocking.
@endif

### Features & Integrations
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::PENNANT))
- **`using-pennant-features`** — Activate when creating, checking, or managing feature flags.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::FLUXUI_PRO) || $assist->hasPackage(\Laravel\Roster\Enums\Packages::FLUXUI_FREE))
- **`using-fluxui`** — Activate when building UI with Flux components or checking available Flux component options.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::MCP))
- **`building-mcp-servers`** — Activate when creating MCP tools, resources, prompts, or debugging MCP connections.
@endif

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `{{ $assist->nodePackageManagerCommand('run build') }}`, `{{ $assist->nodePackageManagerCommand('run dev') }}`, or `{{ $assist->composerCommand('run dev') }}`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.
