# Inertia v2 to v3 Upgrade Specialist

You are an expert Inertia upgrade specialist with deep knowledge of both Inertia v2 and v3. Your task is to systematically upgrade the application from Inertia v2 to v3 while ensuring all functionality remains intact. You understand the nuances of breaking changes and can identify affected code patterns with precision.

## Core Principle: Documentation-First Approach

**IMPORTANT:** Always use the `search-docs` tool whenever you need:
- Specific code examples for implementing Inertia v3 features
- Clarification on breaking changes or new syntax
- Verification of upgrade patterns before applying them
- Examples of correct usage for new directives or methods

The official Inertia documentation is your primary source of truth. Consult it before making assumptions or implementing changes.

## Upgrade Process

Follow this systematic process to upgrade the application:

### 1. Assess Current State

Before making any changes:

- Check `composer.json` for the current `inertiajs/inertia-laravel` version constraint
- Check `package.json` for the current `@inertiajs/*` adapter version
- Run `{{ $assist->composerCommand('show inertiajs/inertia-laravel') }}` to confirm installed server version
- Identify all Inertia pages in `{{ $assist->inertia()->pagesDirectory() }}`
- Review `config/inertia.php` for current configuration
- Review your Vite and SSR setup if the application server-renders Inertia pages

### 2. Create Safety Net

- Ensure you're working on a dedicated branch
- Run the existing test suite to establish baseline
- Note any components with complex JavaScript interactions

### 3. Analyze Codebase for Breaking Changes

Search the codebase for patterns affected by v3 changes:

**High Priority Searches:**
- `router.on('invalid'` or `inertia:invalid` - Rename to `httpException`
- `router.on('exception'` or `inertia:exception` - Rename to `networkError`
- `router.cancel(` - Renamed to `router.cancelAll()`
- `defaults: { future` or `future: {` - The `future` namespace has been removed
- `hideProgress(` or `revealProgress(` - Use the `progress` object instead
- `Inertia::lazy(` or `LazyProp` - Replace with `Inertia::optional()`
- `config/inertia.php` - Configuration structure has changed

**Medium Priority Searches:**
- `qs` imports - Install `qs` directly if the application uses it
- `lodash-es` imports - Install `lodash-es` directly if the application uses it
- `axios` imports or interceptors - Decide whether the app should keep Axios or rely on Inertia's built-in HTTP client
- `Inertia\\Testing\\Concerns\\Has`, `Matching`, or `Debugging` - Deprecated traits removed in v3
- `require(` in frontend code - Inertia packages are now ESM-only
@if($usesReact)
- `import { Deferred }` - React deferred partial reload behavior changed
@endif
@if($usesSvelte)
- Non-runes Svelte components - Update to Svelte 5 runes syntax (`$props()`, `$state()`, `$effect()`, etc.)
@endif

**Low Priority Searches:**
- `vite build --ssr` or `inertia:start-ssr` in development scripts - Dev SSR flow changed when using `@inertiajs/vite`
- `only`, `except`, `Deferred`, or `WhenVisible` with nested props - Dot notation support improved
- `clearHistory` or `encryptHistory` - These page object keys are now omitted unless `true`

### 4. Apply Changes Systematically

For each category of changes:

1. **Search** for affected patterns using grep/search tools
2. **Consult documentation** - Use `search-docs` tool to verify correct upgrade patterns and examples
3. **List** all files that need modification
4. **Apply** the fix consistently across all occurrences
5. **Verify** each change doesn't break functionality

### 5. Update Dependencies

After code changes are complete:

- `{{ $assist->composerCommand('require inertiajs/inertia-laravel:^3.0') }}`
@if($usesReact)
- `{{ $assist->nodePackageManagerCommand('install @inertiajs/react@^3.0') }}`
@endif
@if($usesVue)
- `{{ $assist->nodePackageManagerCommand('install @inertiajs/vue3@^3.0') }}`
@endif
@if($usesSvelte)
- `{{ $assist->nodePackageManagerCommand('install @inertiajs/svelte@^3.0') }}`
@endif
- `{{ $assist->nodePackageManagerCommand('install @inertiajs/vite@^3.0') }}`
- `{{ $assist->artisanCommand('vendor:publish --provider="Inertia\\\\ServiceProvider" --force') }}`
- `{{ $assist->artisanCommand('view:clear') }}`

### 6. Test and Verify

- Run the full test suite
- Manually test critical user flows
- Check browser console for JavaScript errors
- Verify error handling, deferred props, and form submission flows still behave correctly

## Execution Strategy

When upgrading, maximize efficiency by:

- **Batch similar changes** - Group all config updates, then all routing updates, etc.
- **Use parallel agents** for independent file modifications
- **Prioritize high-impact changes** that could cause immediate failures
- **Test incrementally** - Verify after each category of changes

## Important Notes

- Inertia v3 requires PHP 8.2+, Laravel 11+, and Node 20+
@if($usesReact)
- React users must upgrade to React 19+
@endif
@if($usesSvelte)
- Svelte users must upgrade to Svelte 5+ and update components to Svelte 5 runes syntax
@endif
- Axios removal usually does not require code changes
- If the application imports `qs`, install it directly instead of rewriting query handling blindly
- After upgrading, republish the config file and clear cached views because the `@inertia` Blade directive output changed

---

# Upgrading from v2 to v3

Inertia v3 introduces significant improvements including removal of legacy dependencies, streamlined configuration, and better developer experience. This guide covers all breaking changes and migration steps.

## Requirements

Before upgrading, ensure your environment meets these minimum requirements:

- PHP 8.2+
- Laravel 11+
- Node 20+
@if($usesReact)
- React 19+
@endif
@if($usesSvelte)
- Svelte 5+ with Svelte 5 runes syntax (`$props()`, `$state()`, `$effect()`, etc.)
@endif

## Installation

Update your server-side adapter by running `{{ $assist->composerCommand('require inertiajs/inertia-laravel:^3.0') }}`.

Update your client-side adapter:

@if($usesReact)
- `{{ $assist->nodePackageManagerCommand('install @inertiajs/react@^3.0') }}`
@endif
@if($usesVue)
- `{{ $assist->nodePackageManagerCommand('install @inertiajs/vue3@^3.0') }}`
@endif
@if($usesSvelte)
- `{{ $assist->nodePackageManagerCommand('install @inertiajs/svelte@^3.0') }}`
@endif

You may also install the optional Vite plugin, which simplifies page resolution and SSR configuration:

- `{{ $assist->nodePackageManagerCommand('install @inertiajs/vite@^3.0') }}`

After updating, republish the config and clear caches:

- `{{ $assist->artisanCommand('vendor:publish --provider="Inertia\\\\ServiceProvider" --force') }}`
- `{{ $assist->artisanCommand('view:clear') }}`

## High-impact changes

These changes are most likely to affect your application and should be reviewed carefully.

### Axios removed

Inertia v3 no longer ships with or requires Axios. For most applications, this requires no changes. The built-in HTTP client still supports interceptors, and applications that use Axios directly may keep Axios by installing it themselves or by using the Axios adapter.

- `{{ $assist->nodePackageManagerCommand('install axios') }}`

### `qs` dependency removed

The `qs` package is no longer bundled with `@inertiajs/core`. Inertia still handles its own query strings internally, but you should install `qs` directly if your application imports it.

- `{{ $assist->nodePackageManagerCommand('install qs') }}`

### `lodash-es` dependency removed

The `lodash-es` package has been replaced with `es-toolkit` and is no longer included as a dependency of `@inertiajs/core`. You should install `lodash-es` directly if your application imports it.

- `{{ $assist->nodePackageManagerCommand('install lodash-es') }}`

### Event renames

Two global events have been renamed for clarity:

@boostsnippet('Global Event Renames', 'js')
// Before (v2)
router.on('invalid', (event) => {})
router.on('exception', (event) => {})

// After (v3)
router.on('httpException', (event) => {})
router.on('networkError', (event) => {})
@endboostsnippet

If you use document-level event listeners, update the event names accordingly (e.g. `document.addEventListener('inertia:httpException', ...)`).

You may also handle these events per-visit using the new `onHttpException` and `onNetworkError` callbacks:

@boostsnippet('Per-Visit Event Callbacks', 'js')
router.post('/users', data, {
    onHttpException: (response) => {
        return false
    },
    onNetworkError: (error) => {},
})
@endboostsnippet

Returning `false` from `onHttpException` or calling `event.preventDefault()` on the global `httpException` event keeps Inertia from navigating away to its error page.

### `router.cancel()` renamed to `router.cancelAll()`

@boostsnippet('Cancel Rename', 'js')
// Before (v2)
router.cancel()

// After (v3)
router.cancelAll()
router.cancelAll({ async: false, prefetch: false })
@endboostsnippet

### Future options removed

The `future` configuration namespace has been removed. The four v2 future options are now always enabled and can no longer be configured:

@boostsnippet('Future Options Removed', 'js')
// Before (v2)
createInertiaApp({
    defaults: {
        future: {
            preserveEqualProps: true,
            useDataInertiaHeadAttribute: true,
            useDialogForErrorModal: true,
            useScriptElementForInitialPage: true,
        },
    },
})

// After (v3)
createInertiaApp({
    // ...
})
@endboostsnippet

Initial page data is now always passed through a `<script type="application/json">` element. The old `data-page` attribute approach is no longer supported.

### Progress exports removed

The named exports `hideProgress()` and `revealProgress()` have been removed. If you need programmatic control, use the adapter's exported `progress` object instead.

@if($usesReact)
@boostsnippet('Progress Exports React', 'js')
import { progress } from '@inertiajs/react'

progress.hide()
progress.reveal()
@endboostsnippet
@endif
@if($usesVue)
@boostsnippet('Progress Exports Vue', 'js')
import { progress } from '@inertiajs/vue3'

progress.hide()
progress.reveal()
@endboostsnippet
@endif
@if($usesSvelte)
@boostsnippet('Progress Exports Svelte', 'js')
import { progress } from '@inertiajs/svelte'

progress.hide()
progress.reveal()
@endboostsnippet
@endif

### `LazyProp` removed

The deprecated `Inertia::lazy()` method and `LazyProp` class have been removed. Use `Inertia::optional()` instead:

@boostsnippet('LazyProp Migration', 'php')
// Before (v2)
return Inertia::render('Users/Index', [
    'users' => Inertia::lazy(fn () => User::all()),
]);

// After (v3)
return Inertia::render('Users/Index', [
    'users' => Inertia::optional(fn () => User::all()),
]);
@endboostsnippet

## Medium-impact changes

### Config restructuring

The `config/inertia.php` file structure has changed. After upgrading, republish it with `{{ $assist->artisanCommand('vendor:publish --provider="Inertia\\\\ServiceProvider" --force') }}` and then re-apply any customizations on top of the new structure.

@boostsnippet('Config Restructuring', 'php')
// Before (v2) - config/inertia.php
'testing' => [
    'ensure_pages_exist' => true,
    'page_paths' => [resource_path('js/Pages')],
    'page_extensions' => ['js', 'jsx', 'svelte', 'ts', 'tsx', 'vue'],
],

// After (v3) - config/inertia.php
'pages' => [
    'ensure_pages_exist' => false,
    'paths' => [resource_path('js/Pages')],
    'extensions' => ['js', 'jsx', 'svelte', 'ts', 'tsx', 'vue'],
],

'testing' => [
    'ensure_pages_exist' => true,
],
@endboostsnippet

@if($usesReact)
### `Deferred` component behavior (React)

The React `<Deferred>` component no longer resets to its fallback during partial reloads. Existing content now stays visible while new data loads, which matches the Vue and Svelte behavior. A `reloading` slot prop is available when you want to show loading state during those partial reloads.

@endif

### Form `processing` reset timing

The `useForm` helper now resets `processing` and `progress` inside `onFinish`, not immediately when a response arrives. If you depend on the exact timing of `form.processing`, re-test those flows after upgrading.

### Testing concerns removed

The deprecated `Inertia\Testing\Concerns\Has`, `Matching`, and `Debugging` traits have been removed. They were replaced long ago by `AssertableInertia`, so no action is required unless your application still references those traits directly.

## Other changes

### Blade components

Inertia now provides `<x-inertia::head>` and `<x-inertia::app>` Blade components as an alternative to the `@inertiaHead` and `@inertia` directives. The head component accepts fallback content via its slot that only renders when SSR is not active, solving the long-standing issue of duplicate `<title>` tags in SSR applications. The existing directives continue to work and require no changes.

### ES2022 build target

Inertia packages now target ES2022, up from ES2020 in v2. You may use the `@vitejs/plugin-legacy` Vite plugin if your application needs to support older browsers.

### Optional Vite plugin

The new `@inertiajs/vite` plugin can simplify component resolution and SSR configuration. If you adopt it, review the official examples before changing your `createInertiaApp()` bootstrap.

### SSR in development

When using `@inertiajs/vite`, SSR now works in development by simply running your normal Vite dev server. You no longer need `vite build --ssr` or `php artisan inertia:start-ssr` during development.

### Middleware priority

The Inertia middleware is now automatically registered at the correct priority, so no manual middleware-priority customization is required.

### Nested prop types

Nested `Inertia::optional()`, `Inertia::defer()`, and `Inertia::merge()` values now resolve correctly inside closures and nested arrays. On the client side, `only`, `except`, `Deferred`, and `WhenVisible` support dot-notation paths for nested props.

@boostsnippet('Nested Prop Types', 'php')
return Inertia::render('Dashboard', [
    'auth' => fn () => [
        'user' => Auth::user(),
        'notifications' => Inertia::defer(fn () => Auth::user()->unreadNotifications),
        'invoices' => Inertia::optional(fn () => Auth::user()->invoices),
    ],
]);
@endboostsnippet

### ESM-only

All Inertia packages are now ESM-only. Replace any CommonJS `require()` imports with `import` statements.

### Page object changes

The `clearHistory` and `encryptHistory` keys are now omitted from the page object unless they are `true`. If you inspect raw page payloads in custom integrations or tests, update those expectations.

## Next steps: New features in v3

After completing the upgrade, the following new features are available. Do **not** refactor existing code to adopt these features as part of the upgrade. Just complete the breaking changes above. These are listed as next steps so you can explore them separately.

- **Standalone HTTP requests (`useHttp`)** - Make HTTP requests without triggering page visits. Supports reactive state, error handling, file upload progress, request cancellation, optimistic updates, and precognition.
- **Optimistic updates** - Chain `router.optimistic()` before a visit to apply changes instantly on the client. Props revert automatically on failure. Works with router visits, `<Form>`, `useForm`, and `useHttp`.
- **Instant visits** - Swap to the target page component immediately via `<Link href="/dashboard" component="Dashboard">` while the server request fires in the background.
- **Layout props (`useLayoutProps`)** - Persistent layouts can declare defaults that pages override via `setLayoutProps()`. Supports named layouts, nested layouts, and static props.
- **Exception handling (`handleExceptionsUsing`)** - Full control over error page rendering with access to shared data via `withSharedData()`.
- **Default layout** - Set a default layout in `createInertiaApp()` instead of on every page.
- **Form component generics** - TypeScript generics for type-safe errors and slot props.
- **Enum support** - Use PHP enums directly in `Inertia::render()` responses.
- **`preserveErrors` option** - Preserve validation errors during partial reloads.
- **Deferred `reloading` prop** - Show loading indicators during partial reloads across all adapters.

Consult the `search-docs` tool for implementation details when you're ready to adopt any of these features.

## Getting help

If you encounter issues during the upgrade:

- Check the [upgrade guide](https://inertiajs.com/docs/v3/getting-started/upgrade-guide) for the latest details
- Visit the [GitHub discussions](https://github.com/inertiajs/inertia/discussions) for community support
