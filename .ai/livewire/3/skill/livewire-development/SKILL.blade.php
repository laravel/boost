---
name: livewire-development
description: >-
  Develop reactive Livewire 3 components. MUST activate when creating, updating, or modifying
  Livewire components; working with wire:model, wire:click, wire:loading, or any wire: directives;
  adding real-time updates, loading states, or reactivity; debugging component behavior;
  writing Livewire tests; or when the user mentions Livewire, component, counter, or reactive UI.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Livewire Development

## When to Apply

Activate this skill when:
- Creating new Livewire components
- Modifying existing component state or behavior
- Debugging reactivity or lifecycle issues
- Writing Livewire component tests
- Adding Alpine.js interactivity to components
- Working with wire: directives

## Core Patterns

### Creating Components

Use the `{{ $assist->artisanCommand('make:livewire [Posts\\CreatePost]') }}` Artisan command to create new components.

### Fundamental Concepts

- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend; they're like regular HTTP requests. Always validate form data and run authorization checks in Livewire actions.
- Use the `search-docs` tool to find exact version-specific documentation for how to write Livewire and Livewire tests.

## Livewire 3 Specifics

### Key Changes From Livewire 2
- These things changed in Livewire 3, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine Integration
- Alpine is now included with Livewire; don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

## Best Practices

### Component Structure
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.

### Using Keys in Loops
Add `wire:key` in loops:
@verbatim
```blade
@foreach ($items as $item)
    <div wire:key="item-{{ $item->id }}">
        {{ $item->name }}
    </div>
@endforeach
```
@endverbatim

### Lifecycle Hooks
Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:
@verbatim
<code-snippet name="Lifecycle Hook Examples" lang="php">
public function mount(User $user) { $this->user = $user; }
public function updatedSearch() { $this->resetPage(); }
</code-snippet>
@endverbatim

## JavaScript Hooks

You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:
@verbatim
<code-snippet name="Livewire Init Hook Example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>
@endverbatim

## Testing

@verbatim
<code-snippet name="Example Livewire Component Test" lang="php">
Livewire::test(Counter::class)
    ->assertSet('count', 0)
    ->call('increment')
    ->assertSet('count', 1)
    ->assertSee(1)
    ->assertStatus(200);
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Testing Livewire Component Exists on Page" lang="php">
$this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
</code-snippet>
@endverbatim

## Common Pitfalls

- Forgetting `wire:key` in loops causes unexpected behavior when items change
- Using `wire:model` expecting real-time updates (use `wire:model.live` instead in v3)
- Not validating/authorizing in Livewire actions (treat them like HTTP requests)
- Including Alpine.js separately when it's already bundled with Livewire 3
