@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Livewire

- **CRITICAL**: ALWAYS use `search-docs` tool for version-specific Livewire documentation and updated code examples.
- Create components: `{{ $assist->artisanCommand('make:livewire [Posts\\CreatePost]') }}`.
- State lives on server; UI reflects it. Validate and authorize in actions (they're like HTTP requests).
- Always add `wire:key` in loops. Use `wire:loading` for loading states.
- Use/activate 'livewire-development' every time you're working with livewire related task.
