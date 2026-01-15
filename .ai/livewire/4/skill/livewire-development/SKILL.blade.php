---
name: livewire-development
description: >-
  Develop reactive Livewire 4 components. MUST activate when creating, updating, or modifying
  Livewire components; working with wire:model, wire:click, wire:loading, or any wire: directives;
  adding real-time updates, loading states, or reactivity; debugging component behavior;
  writing Livewire tests; or when the user mentions Livewire, component, counter, or reactive UI.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Livewire Development

## When to Apply

- Creating or modifying Livewire components
- Using wire: directives (model, click, loading, sort, intersect)
- Implementing islands or async actions
- Writing Livewire component tests

## Core Pattern

### Creating Components

@boostsnippet("Component Creation Commands", "bash")
# Single-file component (default in v4)
{{ $assist->artisanCommand('make:livewire create-post') }}

# Multi-file component
{{ $assist->artisanCommand('make:livewire create-post --mfc') }}

# Class-based component (v3 style)
{{ $assist->artisanCommand('make:livewire create-post --class') }}

# With namespace
{{ $assist->artisanCommand('make:livewire Posts/CreatePost') }}
@endboostsnippet

### Converting Between Formats

Use `{{ $assist->artisanCommand('livewire:convert create-post') }}` to convert between single-file, multi-file, and class-based formats.

### Component Format Reference

| Format | Flag | Structure |
|--------|------|-----------|
| Single-file (SFC) | default | PHP + Blade in one file |
| Multi-file (MFC) | `--mfc` | Separate PHP class, Blade, JS, tests |
| Class-based | `--class` | Traditional v3 style class |
| View-based | ⚡ prefix | Blade-only with functional state |

**Single-file component (default in v4):**
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
    <button wire:click="increment">Count: {{ $count }}</button>
</div>
@endboostsnippet

## Key v4 Changes

- `wire:model` ignores child events by default → use `wire:model.deep`
- `wire:scroll` renamed → `wire:navigate:scroll`
- Component tags must be properly closed (`<livewire:name />`)
- Config keys: `layout` → `component_layout`, `lazy_placeholder` → `component_placeholder`
- `wire:transition` uses View Transitions API (modifiers removed)

## New Features

| Feature | Usage | Purpose |
|---------|-------|---------|
| Islands | `@island(name: 'stats')` | Isolated update regions |
| Async | `wire:click.async` or `#[Async]` | Non-blocking actions |
| Deferred | `defer` attribute | Load after page render |
| Bundled | `lazy.bundle` | Load multiple together |

Use `search-docs` with "livewire 4 islands" or "livewire async" for detailed examples.

## New Directives

| Directive | Purpose |
|-----------|---------|
| `wire:sort` | Drag-and-drop sorting |
| `wire:intersect` | Viewport intersection detection |
| `wire:ref` | Element references for JS |

## Best Practices

- Always use `wire:key` in loops
- Use `wire:loading` for loading states
- Use `wire:model.live` for instant updates (default is debounced)
- Validate and authorize in actions (treat like HTTP requests)

## JavaScript Integration

For interceptors and hooks, see [reference/javascript-hooks.md](reference/javascript-hooks.md).

## Testing

@boostsnippet("Testing Example", "php")
Livewire::test(Counter::class)
    ->assertSet('count', 0)
    ->call('increment')
    ->assertSet('count', 1);
@endboostsnippet

Use `search-docs` with "livewire testing" for comprehensive testing patterns.

## Verification

1. Browser console: Check for JS errors
2. Network tab: Verify Livewire requests return 200
3. Ensure `wire:key` on all `@foreach` loops
4. Use `search-docs` with "livewire debugging" for troubleshooting

## Common Pitfalls

- Missing `wire:key` in loops → unexpected re-rendering
- Expecting `wire:model` real-time → use `wire:model.live`
- Unclosed component tags → syntax errors in v4
- Using deprecated config keys or JS hooks
- Including Alpine.js separately (already bundled in Livewire 4)
