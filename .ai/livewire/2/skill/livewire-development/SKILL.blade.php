---
name: livewire-development
description: "Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, loading states, migrating from Livewire 1 to 2, and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Livewire Development

Use `search-docs` for Livewire 2 syntax and patterns. Create components with `{{ $assist->artisanCommand('make:livewire [Posts\\CreatePost]') }}`.

## Consistency First

Follow the project's existing component structure, namespace, and event conventions over anything here.

## Core Concepts

- State lives on the server; the UI reflects it.
- Livewire requests are HTTP requests: validate and authorize inside actions.
- Components need a single root element.

## Livewire 2 Gotchas

- `wire:model` is live by default, which can cause performance issues; v3 reverses this.
- Components typically live in `App\Http\Livewire`.
- Events use `emit()`, `emitTo()`, `emitSelf()`, and `dispatchBrowserEvent()`.
- Alpine is included separately from Livewire.
- Use `wire:loading` and `wire:dirty` for UI states, and `mount()` / `updatedFoo()` hooks for initialization and reactive side effects.
- Hook JS in a `livewire:load` listener (`Livewire.onPageExpired`, `Livewire.onError`).

## Testing

Use `Livewire::test()` for components and `assertSeeLivewire()` to check a component is on a page.

## Common Pitfalls

- Missing `wire:key` in loops causes unexpected behavior when items change.
- Skipping validation or authorization in actions.
- Forgetting `wire:model` is live by default.
