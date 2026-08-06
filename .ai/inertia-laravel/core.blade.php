@scoped(['app/Http/**', 'routes/**', 'resources/js/**'])
# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `{{ $assist->inertia()->pagesDirectory() }}` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
@if($assist->hasPackage(\Laravel\Boost\Support\PackageRegistry::INERTIA_REACT))
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.
@elseif($assist->hasPackage(\Laravel\Boost\Support\PackageRegistry::INERTIA_VUE))
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.
@elseif($assist->hasPackage(\Laravel\Boost\Support\PackageRegistry::INERTIA_SVELTE))
- IMPORTANT: Activate `inertia-svelte-development` when working with Inertia Svelte client-side patterns.
@endif

@if($assist->inertia()->gte('3.0.0'))
# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.
@elseif($assist->inertia()->gte('2.0.0'))
# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scrolling (merging props + `WhenVisible`), lazy loading on scroll, polling, prefetching.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
@else
# Inertia v1

- Inertia v1 does not support the following v2 features: deferred props, infinite scrolling (merging props + `WhenVisible`), lazy loading on scroll, polling, or prefetching. Do not use these.
@endif
@endscoped
