---
name: volt-development
description: "Develops single-file Livewire components with Volt. Activates when creating Volt components, converting Livewire to Volt, working with @volt directive, functional or class-based Volt APIs; or when the user mentions Volt, single-file components, functional Livewire, or inline component logic in Blade files."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Volt development

## Documentation

Use `search-docs` for detailed Volt patterns and documentation.

## Basic usage

Create components with `{{ $assist->artisanCommand('make:volt [name] [--test] [--pest]') }}`.

Important: Before creating a Volt component, check whether the existing ones use the functional or class-based style.

### Functional components

@boostsnippet("Volt Functional Component", "php")
@@volt
<?php
use function Livewire\Volt\{state, computed};

state(['count' => 0]);

$increment = fn () => $this->count++;
$double = computed(fn () => $this->count * 2);
?>

<div>
    <h1>Count: @{{ $count }} (Double: @{{ $this->double }})</h1>
    <button wire:click="increment">+</button>
</div>
@@endvolt
@endboostsnippet

### Class-based components

@boostsnippet("Volt Class-based Component", "php")
<?php

use Livewire\Volt\Component;

new class extends Component {
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
} ?>

<div>
    <h1>@{{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>
@endboostsnippet

## Testing

Put tests in the existing Volt test directory or in `tests/Feature/Volt`:

@boostsnippet("Volt Test Example", "php")
use Livewire\Volt\Volt;

test('counter increments', function () {
    Volt::test('counter')
        ->assertSee('Count: 0')
        ->call('increment')
        ->assertSee('Count: 1');
});
@endboostsnippet

## Verification

1. Check whether existing components use the functional or class-based style
2. Test the component with `Volt::test()`

## Common pitfalls

- Not checking the existing style (functional or class-based) before creating a component
- Forgetting the `@volt` directive wrapper
- Omitting the `--test` or `--pest` flag when you need tests
