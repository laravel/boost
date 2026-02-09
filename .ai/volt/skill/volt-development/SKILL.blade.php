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
# Volt Development

## When to Apply

Activate this skill when:

- Creating Volt single-file components
- Converting traditional Livewire components to Volt
- Testing Volt components

## Documentation

Use `search-docs` for detailed Volt patterns and documentation.

## Basic Usage

Create components with `{{ $assist->artisanCommand('make:volt [name] [--test] [--pest]') }}`.

Important: Check existing Volt components to determine if they use functional or class-based style before creating new ones.

### Functional Components

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

### Class-Based Components

@boostsnippet("Volt Class-based Component", "php")
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

Tests go in existing Volt test directory or `tests/Feature/Volt`:

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

1. Check existing components for functional vs class-based style
2. Test component with `Volt::test()`

## Common Pitfalls

- Not checking existing style (functional vs class-based) before creating
- Forgetting `@volt` directive wrapper
- Missing `--test` or `--pest` flag when tests are needed
