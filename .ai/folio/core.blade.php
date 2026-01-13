@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Laravel Folio

- File-based routing: `resources/views/pages/` files become routes automatically.
- Create pages: `{{ $assist->artisanCommand('folio:page [name]') }}`. List routes: `{{ $assist->artisanCommand('folio:list') }}`.
- Add named routes to pages using `name('route.name')` at the top.
- Use `search-docs` for Folio patterns (middleware, model binding, etc.).
- Use/activate 'using-folio-routing' for routing conventions and middleware patterns.
