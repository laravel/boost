@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Laravel Folio

- **CRITICAL**: ALWAYS use `search-docs` tool for version-specific Folio documentation and updated code examples.
- File-based routing: `resources/views/pages/` files become routes automatically.
- Create pages: `{{ $assist->artisanCommand('folio:page [name]') }}`. List routes: `{{ $assist->artisanCommand('folio:list') }}`.
- Use/activate 'folio-routing' for routing conventions and middleware patterns.
