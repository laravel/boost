@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Livewire

- Create components: `{{ $assist->artisanCommand('make:livewire [Posts\\CreatePost]') }}`.
- State lives on server; UI reflects it. Validate and authorize in actions (they're like HTTP requests).
- Always add `wire:key` in loops. Use `wire:loading` for loading states.
- Use `search-docs` for version-specific Livewire documentation.
- Use/activate 'building-livewire-components' evrytime you;re workign with livewire related task.
