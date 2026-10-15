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
# Livewire development

## Documentation

Use `search-docs` for detailed Livewire 2 patterns and documentation.

## Basic usage

### Creating components

Use the `{{ $assist->artisanCommand('make:livewire [Posts\\CreatePost]') }}` Artisan command to create new components.

### Fundamental concepts

- Keep state on the server, and let the UI reflect it.
- Every Livewire request hits the Laravel backend, like a regular HTTP request. Always validate form data and run authorization checks in Livewire actions.

## Livewire 2 specifics

- `wire:model` is live by default, so it updates in real time without a modifier.
- Components usually live in the `App\Http\Livewire` namespace.
- Use `emit()`, `emitTo()`, `emitSelf()`, and `dispatchBrowserEvent()` for events.
- Alpine is included separately from Livewire.

## Best practices

### Component structure

- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for loading states.

### Using keys in loops

@boostsnippet("Wire Key in Loops", "blade")
@foreach ($items as $item)
    <div wire:key="item-{{ $item->id }}">
        {{ $item->name }}
    </div>
@endforeach
@endboostsnippet

### Lifecycle hooks

For initialization and reactive side effects, prefer lifecycle hooks such as `mount()` and `updatedFoo()`:

@boostsnippet("Lifecycle Hook Examples", "php")
public function mount(User $user) { $this->user = $user; }
public function updatedSearch() { $this->resetPage(); }
@endboostsnippet

## JavaScript hooks

To hook into Livewire initialization, listen for `livewire:load`:

@boostsnippet("Livewire Load Hook Example", "js")
document.addEventListener('livewire:load', function () {
    Livewire.onPageExpired(() => {
        alert('Your session expired');
    });

    Livewire.onError(status => console.error(status));
});
@endboostsnippet

## Testing

@boostsnippet("Example Livewire Component Test", "php")
Livewire::test(Counter::class)
    ->assertSet('count', 0)
    ->call('increment')
    ->assertSet('count', 1)
    ->assertSee(1)
    ->assertStatus(200);
@endboostsnippet

@boostsnippet("Testing Livewire Component Exists on Page", "php")
$this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
@endboostsnippet

## Common pitfalls

- Forgetting `wire:key` in loops causes unexpected behavior when items change
- Not validating or authorizing in Livewire actions (treat them like HTTP requests)
- Forgetting that `wire:model` is live by default in v2 (live updates may cause performance issues)
