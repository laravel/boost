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
# Livewire development

## Documentation

Use `search-docs` for detailed Livewire 3 patterns and documentation.

## Basic usage

### Creating components

Use the `{{ $assist->artisanCommand('make:livewire [Posts\\CreatePost]') }}` Artisan command to create new components.

### Fundamental concepts

- Keep state on the server, and let the UI reflect it.
- Every Livewire request hits the Laravel backend, like a regular HTTP request. Always validate form data and run authorization checks in Livewire actions.

## Livewire 3 specifics

### Key changes from Livewire 2

These things changed in Livewire 3, but this application may not use them yet. Check the application's setup and follow its existing conventions.
- For real-time updates, use `wire:model.live`. `wire:model` is now deferred by default.
- Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
- Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
- Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New directives

- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, and `wire:target` are available.

### Alpine integration

- Livewire now includes Alpine. Don't include Alpine.js manually.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

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

To hook into Livewire initialization, listen for `livewire:init`:

@boostsnippet("Livewire Init Hook Example", "js")
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();
                alert('Your session expired');
            }
        });
    });

    Livewire.hook('commit', ({ fail }) => {
        fail(() => console.error('Livewire commit failed'));
    });
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
- Using `wire:model` and expecting real-time updates (in v3, use `wire:model.live` instead)
- Not validating or authorizing in Livewire actions (treat them like HTTP requests)
- Including Alpine.js separately when Livewire 3 already bundles it
