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

This project has domain-specific skills available. **You MUST activate the relevant skill** whenever you work in that domain—don't wait until you're stuck.

### Frontend Development
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::LIVEWIRE))
- **`building-livewire-components`** — ALWAYS activate when working with Livewire. This includes: creating/updating components, wire: directives (wire:model, wire:click, wire:loading), real-time updates, loading states, reactivity, component tests, or any App\Livewire namespace code.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_LARAVEL) || $assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE) || $assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_REACT) || $assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
- **`building-inertia-apps`** — ALWAYS activate when working with Inertia. This includes: creating pages, forms, navigation, Vue/React components, props, useForm, Link, router, or any SPA patterns.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::TAILWINDCSS))
- **`using-tailwindcss`** — ALWAYS activate when styling. This includes: adding/changing CSS classes, responsive design, dark mode, gradients, spacing, layout, flex, grid, or any visual/UI changes.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::VOLT))
- **`using-volt-components`** — ALWAYS activate when working with Volt. This includes: creating Volt components, @volt directive, functional or class-based APIs, or single-file component patterns.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::FOLIO))
- **`using-folio-routing`** — ALWAYS activate when working with pages. This includes: creating Folio pages, file-based routes, route parameters, model binding, middleware, or resources/views/pages directory.
@endif

### Testing
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::PEST))
- **`testing-with-pest`** — ALWAYS activate when testing. This includes: writing tests, unit/feature tests, assertions, Livewire tests, datasets, mocking, debugging failures, or verifying functionality.
@endif

### Features & Integrations
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::PENNANT))
- **`using-pennant-features`** — ALWAYS activate when working with feature flags. This includes: creating/checking flags, @feature directive, conditional features, A/B testing, or gradual rollouts.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::FLUXUI_PRO) || $assist->hasPackage(\Laravel\Roster\Enums\Packages::FLUXUI_FREE))
- **`using-fluxui`** — ALWAYS activate when building UI components. This includes: flux: components, buttons, forms, modals, inputs, or replacing HTML elements with Flux.
@endif
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::MCP))
- **`building-mcp-servers`** — ALWAYS activate when working with MCP. This includes: creating tools/resources/prompts, routes/ai.php, AI integrations, or debugging MCP connections.
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
