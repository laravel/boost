---
name: livewire-development
description: "Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Livewire Development

Use `search-docs` for Livewire 4 syntax and patterns.

## Consistency First

Before creating a component, inspect existing ones in `{{ $assist->appPath('Livewire/') }}`, `resources/views/components/`, and `resources/views/livewire/`. If the project uses a consistent format (SFC, MFC, or class-based) and directory structure, follow it even where it differs from the v4 defaults. Fall back to v4 defaults (SFC in `resources/views/components/`) only when no convention exists.

Check `config/livewire.php` for `make_command.type`, `make_command.emoji`, `component_locations`, and `component_namespaces`; they change the default format and file location. When `make_command.emoji` is `true` (default), include the ⚡ prefix in filenames you create by hand; when `false`, omit it.

## Creating and Converting Components

| Format | Command | Files |
|--------|---------|-------|
| Single-file (SFC, default) | `{{ $assist->artisanCommand('make:livewire create-post') }}` | `resources/views/components/⚡create-post.blade.php` |
| Full-page SFC | `{{ $assist->artisanCommand('make:livewire pages::create-post') }}` | `resources/views/pages/⚡create-post.blade.php` |
| Multi-file (MFC) | `{{ $assist->artisanCommand('make:livewire create-post --mfc') }}` | `⚡create-post/create-post.php` and `.blade.php` under `resources/views/components/` |
| Class-based | `{{ $assist->artisanCommand('make:livewire create-post --class') }}` | `{{ $assist->appPath('Livewire/CreatePost.php') }}` and `resources/views/livewire/create-post.blade.php` |

Namespaces map to subdirectories: `Posts/CreatePost` becomes `components/posts/⚡create-post.blade.php`.

Convert between formats with `{{ $assist->artisanCommand('livewire:convert create-post') }}`.

## Livewire 4 Gotchas

These changed from v3 but the application may not be updated, so verify its setup first.

- Full-page components use `Route::livewire()`. Config keys renamed: `layout` to `component_layout`, `lazy_placeholder` to `component_placeholder`.
- `wire:model` ignores child events by default (`wire:model.deep` restores the old behavior). `wire:scroll` is now `wire:navigate:scroll`.
- Component tags must be closed. `wire:transition` uses the View Transitions API and its modifiers are gone.
- JS: `$wire.$js('name', fn)` is now `$wire.$js.name = fn`; `commit`/`request` hooks are now `interceptMessage()`/`interceptRequest()`. See [reference/javascript-hooks.md](reference/javascript-hooks.md).
- Alpine is bundled; do not include it separately.

## New in v4

- Islands (`@island(name: 'stats')`) isolate update regions.
- Async actions (`wire:click.async`, `#[Async]`) run in parallel.
- `defer` loads after page render; `lazy.bundle` loads several lazy components together.
- Directives: `wire:sort` (drag-and-drop), `wire:intersect`, `wire:ref`, `.renderless`, `.preserve-scroll`. Requesting elements get a `data-loading` attribute.
- `$errors` and `$intercept` are available in Alpine/JS.

## Best Practices

- Put `wire:key` on every looped element; without it re-rendering misbehaves.
- `wire:model` is deferred; use `wire:model.live` for real-time updates.
- Use `wire:loading` for loading states.
- Validate and authorize inside actions, as with HTTP requests.

## Testing

Use `Livewire::test()`; search docs for assertions.

## Verification

Check the browser console for JS errors and that Livewire requests return 200.
