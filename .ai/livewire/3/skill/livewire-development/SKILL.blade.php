---
name: livewire-development
description: "Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, loading states, migrating from Livewire 2 to 3, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Livewire Development

Use `search-docs` for Livewire 3 syntax and patterns. Create components with `{{ $assist->artisanCommand('make:livewire [Posts\\CreatePost]') }}`.

## Consistency First

Follow the project's existing component structure, namespace, layout, and event conventions over anything here. The v2-to-v3 changes below may not have been applied in this application, so verify first.

## Core Concepts

- State lives on the server; the UI reflects it.
- Livewire requests are HTTP requests: validate and authorize inside actions.
- Components need a single root element.

## Livewire 3 Gotchas

- `wire:model` is deferred; use `wire:model.live` for real-time updates.
- Namespace is `App\Livewire`, not `App\Http\Livewire`; the layout is typically `components.layouts.app`.
- Dispatch events with `$this->dispatch()`, not `emit` or `dispatchBrowserEvent`.
- Alpine is bundled with its persist, intersect, collapse, and focus plugins; do not include it separately.
- Available: `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target`; use `wire:loading` and `wire:dirty` for UI states.
- Use `mount()` and `updatedFoo()` hooks for initialization and reactive side effects (e.g. `resetPage()` in `updatedSearch()`).
- Hook JS in a `livewire:init` listener with `Livewire.hook('request' | 'commit', ...)`; search docs for `fail` and `preventDefault` usage.

## Testing

Use `Livewire::test()` for components and `assertSeeLivewire()` to check a component is on a page.

## Common Pitfalls

- Missing `wire:key` in loops causes unexpected behavior when items change.
- Expecting `wire:model` to be real-time.
- Skipping validation or authorization in actions.
- Including Alpine separately.
