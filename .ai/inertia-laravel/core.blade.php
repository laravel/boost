## Inertia

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (`vite.config.js`).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- **CRITICAL**: ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_REACT))
- For client-side patterns, activate: `inertia-react-development`.
@elseif($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
- For client-side patterns, activate: `inertia-vue-development`.
@elseif($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
- For client-side patterns, activate: `inertia-svelte-development`.
@endif
