---
name: livewire-development
description: "Develops reactive Livewire 4 components. Activates when creating, updating, or modifying Livewire components; working with wire:model, wire:click, wire:loading, or any wire: directives; adding real-time updates, loading states, or reactivity; debugging component behavior; writing Livewire tests; or when the user mentions Livewire, component, counter, or reactive UI."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Livewire Development

## When to Apply

Activate this skill when:

- Creating or modifying Livewire components
- Using wire: directives (model, click, loading, sort, intersect)
- Implementing islands or async actions
- Writing Livewire component tests

## Documentation

Use `search-docs` for detailed Livewire 4 patterns and documentation.

## Basic Usage

### Creating Components

```bash
# Single-file component (default in v4)
{{ $assist->artisanCommand('make:livewire create-post') }}

# Multi-file component
{{ $assist->artisanCommand('make:livewire create-post --mfc') }}

# Class-based component (v3 style)
{{ $assist->artisanCommand('make:livewire create-post --class') }}

# With namespace
{{ $assist->artisanCommand('make:livewire Posts/CreatePost') }}
```

### Converting Between Formats

Use `{{ $assist->artisanCommand('livewire:convert create-post') }}` to convert between single-file, multi-file, and class-based formats.

### Choosing a Component Format

Before creating a component, check `config/livewire.php` for directory overrides, which change where files are stored. Then, look at existing files in those directories (defaulting to `app/Livewire/` and `resources/views/livewire/`) to match the established convention.

### Component Format Reference

| Format | Flag | Class Path | View Path |
|--------|------|------------|-----------|
| Single-file (SFC) | default | — | `resources/views/livewire/create-post.blade.php` (PHP + Blade in one file) |
| Multi-file (MFC) | `--mfc` | `app/Livewire/CreatePost.php` | `resources/views/livewire/create-post.blade.php` |
| Class-based | `--class` | `app/Livewire/CreatePost.php` | `resources/views/livewire/create-post.blade.php` |
| View-based | ⚡ prefix | — | `resources/views/livewire/create-post.blade.php` (Blade-only with functional state) |

Namespaced components map to subdirectories: `make:livewire Posts/CreatePost` creates files at `app/Livewire/Posts/CreatePost.php` and `resources/views/livewire/posts/create-post.blade.php`.

### Single-File Component Example

@boostsnippet("Single-File Component Example", "php")
<?php
use Livewire\Component;

new class extends Component {
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
}
?>

<div>
    <button wire:click="increment">Count: @{{ $count }}</button>
</div>
@endboostsnippet

## Livewire 4 Specifics

### Key Changes From Livewire 3

These things changed in Livewire 4, but may not have been updated in this application. Verify this application's setup to ensure you follow existing conventions.

- Use `Route::livewire()` for full-page components (e.g., `Route::livewire('/posts/create', CreatePost::class)`); config keys renamed: `layout` → `component_layout`, `lazy_placeholder` → `component_placeholder`.
- `wire:model` now ignores child events by default (use `wire:model.deep` for old behavior); `wire:scroll` renamed to `wire:navigate:scroll`.
- Component tags must be properly closed; `wire:transition` now uses View Transitions API (modifiers removed).
- JavaScript: `$wire.$js('name', fn)` → `$wire.$js.name = fn`; `commit`/`request` hooks → `interceptMessage()`/`interceptRequest()`.

### New Features

- Component formats: single-file (SFC), multi-file (MFC), view-based components.
- Islands (`@island`) for isolated updates; async actions (`wire:click.async`, `#[Async]`) for parallel execution.
- Deferred/bundled loading: `defer`, `lazy.bundle` for optimized component loading.

| Feature | Usage | Purpose |
|---------|-------|---------|
| Islands | `@island(name: 'stats')` | Isolated update regions |
| Async | `wire:click.async` or `#[Async]` | Non-blocking actions |
| Deferred | `defer` attribute | Load after page render |
| Bundled | `lazy.bundle` | Load multiple together |

### New Directives

- `wire:sort`, `wire:intersect`, `wire:ref`, `.renderless`, `.preserve-scroll` are available for use.
- `data-loading` attribute automatically added to elements triggering network requests.

| Directive | Purpose |
|-----------|---------|
| `wire:sort` | Drag-and-drop sorting |
| `wire:intersect` | Viewport intersection detection |
| `wire:ref` | Element references for JS |
| `.renderless` | Component without rendering |
| `.preserve-scroll` | Preserve scroll position |

## Best Practices

- Always use `wire:key` in loops
- Use `wire:loading` for loading states
- Use `wire:model.live` for instant updates (default is debounced)
- Validate and authorize in actions (treat like HTTP requests)

## Configuration

- `smart_wire_keys` defaults to `true`; new configs: `component_locations`, `component_namespaces`, `make_command`, `csp_safe`.

## Alpine & JavaScript

- `wire:transition` uses browser View Transitions API; `$errors` and `$intercept` magic properties available.
- Non-blocking `wire:poll` and parallel `wire:model.live` updates improve performance.

For interceptors and hooks, see [reference/javascript-hooks.md](reference/javascript-hooks.md).

## Testing

@boostsnippet("Testing Example", "php")
Livewire::test(Counter::class)
    ->assertSet('count', 0)
    ->call('increment')
    ->assertSet('count', 1);
@endboostsnippet

## Verification

1. Browser console: Check for JS errors
2. Network tab: Verify Livewire requests return 200
3. Ensure `wire:key` on all `@foreach` loops

## Common Pitfalls

- Missing `wire:key` in loops → unexpected re-rendering
- Expecting `wire:model` real-time → use `wire:model.live`
- Unclosed component tags → syntax errors in v4
- Using deprecated config keys or JS hooks
- Including Alpine.js separately (already bundled in Livewire 4)
