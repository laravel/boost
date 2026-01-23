# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_REACT))
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.
@elseif($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.
@elseif($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
- IMPORTANT: Activate `inertia-svelte-development` when working with Inertia Svelte client-side patterns.
@endif
