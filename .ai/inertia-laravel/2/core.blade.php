# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `{{ $assist->inertia()->pagesDirectory() }}` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_REACT))
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.
@elseif($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.
@elseif($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
- IMPORTANT: Activate `inertia-svelte-development` when working with Inertia Svelte client-side patterns.
@endif

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scrolling (merging props + `WhenVisible`), lazy loading on scroll, polling, prefetching.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
