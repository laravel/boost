@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Livewire Volt

- Single-file Livewire components: PHP logic and Blade templates in one file.
- Create components: `{{ $assist->artisanCommand('make:volt [name] [--test] [--pest]') }}`.
- Check existing Volt components to determine functional vs class-based style.
- **CRITICAL**: ALWAYS use `search-docs` tool for version-specific Volt documentation and updated code examples.
- Use/activate 'volt-development' for Volt component patterns and testing.
