---
name: livewire-development
description: >-
  Develop reactive Livewire 2 components. MUST activate when creating, updating, or modifying
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

## Livewire 2 Specifics

- `wire:model` is live by default (real-time updates without modifier).
- Components typically exist in the `App\Http\Livewire` namespace.
- Use `emit()`, `emitTo()`, `emitSelf()`, and `dispatchBrowserEvent()` for events.
- Alpine is included separately from Livewire.

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

You can listen for `livewire:load` to hook into Livewire initialization, and `Livewire.onPageExpired` for when the page expires:
@verbatim
<code-snippet name="Livewire Load Hook Example" lang="js">
document.addEventListener('livewire:load', function () {
    Livewire.onPageExpired(() => {
        alert('Your session expired');
    });

    Livewire.onError(status => console.error(status));
});
</code-snippet>
@endverbatim

## Testing Livewire Components

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
- Not validating/authorizing in Livewire actions (treat them like HTTP requests)
- Forgetting that `wire:model` is live by default in v2 (may cause performance issues)
