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

### 2. Create Safety Net

- Ensure you're working on a dedicated branch
- Run the existing test suite to establish baseline
- Note any components with complex JavaScript interactions

### 3. Analyze Codebase for Breaking Changes

Search the codebase for patterns affected by v3 changes:

**High Priority Searches:**
- `axios` or `import axios` - Axios has been removed; replace with `fetch`
- `qs` or `import qs` - qs has been removed; replace with `URLSearchParams`
- `router.cancel(` - Renamed to `router.cancelAll()`
- `Inertia::lazy(` or `LazyProp` - `LazyProp` has been removed; use `Inertia::optional()` instead
- `config/inertia.php` - Configuration structure has changed

**Medium Priority Searches:**
- `onBefore`, `onStart`, `onProgress`, `onFinish`, `onCancel` - Event renames needed
- `resolveComponent` or `setup({` in `app.js`/`app.ts` - Config restructuring
- `createInertiaApp` - Setup callback has changed
- `import { Deferred }` - Now renders children immediately without props

**Low Priority Searches:**
- `Inertia::testing(` or `TestingConcerns` - Removed; use `Inertia::assertComponent()` instead
- `NProgress` or `import.*progress` - Progress exports removed from adapter packages
- Future options (`partialComponent`, `defaultComponent`, `v:if`) - Removed

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
- `{{ $assist->artisanCommand('optimize:clear') }}`

### 6. Test and Verify

- Run the full test suite
- Manually test critical user flows
- Check browser console for JavaScript errors
- Verify all components render correctly

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
- Svelte users must upgrade to Svelte 5+
@endif
- Axios and qs are no longer bundled; use native `fetch` and `URLSearchParams`
- After upgrading, republish the config file and clear cached views

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
- Svelte 5+
@endif
@if($usesVue)
- Vue 3.x
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

After updating, republish the config and clear caches:

- `{{ $assist->artisanCommand('vendor:publish --tag=inertia-config --force') }}`
- `{{ $assist->artisanCommand('optimize:clear') }}`
- `{{ $assist->artisanCommand('view:clear') }}`

## High-impact changes

These changes are most likely to affect your application and should be reviewed carefully.

### Axios removed

Inertia v3 no longer ships with or depends on Axios. All internal HTTP requests now use the native `fetch` API.

If your application imports Axios from Inertia, you must either install it separately or migrate to `fetch`:

@boostsnippet('Axios Migration', 'js')
// Before (v2) - importing Axios from Inertia
import axios from 'axios'
axios.get('/api/users')

// After (v3) - use native fetch
const response = await fetch('/api/users')
const users = await response.json()
@endboostsnippet

If you still need Axios, install it directly with `{{ $assist->nodePackageManagerCommand('install axios') }}`.

### qs removed

The `qs` library is no longer bundled. Use the native `URLSearchParams` API instead:

@boostsnippet('QS Migration', 'js')
// Before (v2)
import qs from 'qs'
const query = qs.stringify({ page: 1, sort: 'name' })

// After (v3) - use native URLSearchParams
const query = new URLSearchParams({ page: '1', sort: 'name' }).toString()
@endboostsnippet

> [!note] Nested parameters
> `URLSearchParams` does not support nested objects like `qs` does. If you rely on deeply nested query string encoding, install `qs` directly.

### Event renames

Several visit event callbacks have been renamed for consistency:

@boostsnippet('Event Renames', 'js')
// Before (v2)
router.visit('/users', {
    onBefore: (visit) => {},
    onStart: (visit) => {},
    onProgress: (progress) => {},
    onFinish: (visit) => {},
    onCancel: () => {},
})

// After (v3)
router.visit('/users', {
    before: (visit) => {},
    start: (visit) => {},
    progress: (progress) => {},
    finish: (visit) => {},
    cancel: () => {},
})
@endboostsnippet

The `on` prefix has been dropped from all event callbacks. This applies everywhere events are used: `router.visit()`, `router.get()`, `router.post()`, `useForm()`, `<Link>`, etc.

### `router.cancel()` renamed to `router.cancelAll()`

@boostsnippet('Cancel Rename', 'js')
// Before (v2)
router.cancel()

// After (v3)
router.cancelAll()
@endboostsnippet

### Future options removed

The experimental "future" options have been removed. These features are now either the default behavior or have been dropped:

@boostsnippet('Future Options Removed', 'js')
// Before (v2) - future options in createInertiaApp
createInertiaApp({
    future: {
        partialComponent: true,
        defaultComponent: true,
    },
    // ...
})

// After (v3) - remove future options entirely
createInertiaApp({
    // ...
})
@endboostsnippet

### Progress exports removed

The progress bar is now built into the core and no longer needs to be imported separately from adapter packages.

@if($usesReact)
@boostsnippet('Progress Exports React', 'js')
// Before (v2)
import { router } from '@inertiajs/react'
import NProgress from 'nprogress'

// After (v3) - progress is built-in, configure via createInertiaApp
createInertiaApp({
    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
    // ...
})
@endboostsnippet
@endif
@if($usesVue)
@boostsnippet('Progress Exports Vue', 'js')
// Before (v2)
import { router } from '@inertiajs/vue3'
import NProgress from 'nprogress'

// After (v3) - progress is built-in, configure via createInertiaApp
createInertiaApp({
    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
    // ...
})
@endboostsnippet
@endif
@if($usesSvelte)
@boostsnippet('Progress Exports Svelte', 'js')
// Before (v2)
import { router } from '@inertiajs/svelte'
import NProgress from 'nprogress'

// After (v3) - progress is built-in, configure via createInertiaApp
createInertiaApp({
    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
    // ...
})
@endboostsnippet
@endif

### `LazyProp` removed

The `LazyProp` class has been removed. Use `Inertia::optional()` instead:

@boostsnippet('LazyProp Migration', 'php')
// Before (v2)
use Inertia\LazyProp;

return Inertia::render('Users/Index', [
    'users' => new LazyProp(fn () => User::all()),
]);

// After (v3)
return Inertia::render('Users/Index', [
    'users' => Inertia::optional(fn () => User::all()),
]);
@endboostsnippet

## Medium-impact changes

### Config restructuring

The `config/inertia.php` file structure has changed. After upgrading, republish it with `{{ $assist->artisanCommand('vendor:publish --tag=inertia-config --force') }}` and re-apply any customizations you had in the old config file.

### `createInertiaApp` setup changes

The `setup` callback in `createInertiaApp` has been restructured. The `App` and `props` are now passed differently depending on your framework.

@if($usesReact)
@boostsnippet('React Setup', 'jsx')
// Before (v2) - React
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
    resolve: name => resolvePageComponent(name, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />)
    },
})

// After (v3) - React
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
    resolve: name => resolvePageComponent(name, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />)
    },
})
@endboostsnippet
@endif
@if($usesVue)
@boostsnippet('Vue Setup', 'js')
// Before (v2) - Vue
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
    resolve: name => resolvePageComponent(name, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})

// After (v3) - Vue (verify setup callback with search-docs)
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
    resolve: name => resolvePageComponent(name, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})
@endboostsnippet
@endif
@if($usesSvelte)
@boostsnippet('Svelte Setup', 'js')
// Before (v2) - Svelte
import { createInertiaApp } from '@inertiajs/svelte'

createInertiaApp({
    resolve: name => resolvePageComponent(name, import.meta.glob('./Pages/**/*.svelte')),
    setup({ el, App, props }) {
        new App({ target: el, props })
    },
})

// After (v3) - Svelte 5 (verify setup callback with search-docs)
import { createInertiaApp } from '@inertiajs/svelte'
import { mount } from 'svelte'

createInertiaApp({
    resolve: name => resolvePageComponent(name, import.meta.glob('./Pages/**/*.svelte')),
    setup({ el, App, props }) {
        mount(App, { target: el, props })
    },
})
@endboostsnippet
@endif

> [!tip] Check the docs
> Use `search-docs` to verify the correct `createInertiaApp` pattern for your framework.

@if($usesReact || $usesVue)
### `Deferred` component behavior

The `<Deferred>` component now renders its children immediately, even before the deferred props have loaded. In v2, it waited for the props before rendering children.

@if($usesReact)
@boostsnippet('Deferred Component React', 'jsx')
// Before (v2) - children only rendered after props loaded
<Deferred props={['users']}>
    <UserList users={users} />
</Deferred>

// After (v3) - children render immediately; props may be undefined initially
<Deferred props={['users']}>
    <UserList users={users ?? []} />
</Deferred>
@endboostsnippet
@endif
@if($usesVue)
@boostsnippet('Deferred Component Vue', 'html')
<!-- Before (v2) - children only rendered after props loaded -->
<Deferred :props="['users']">
    <UserList :users="users" />
</Deferred>

<!-- After (v3) - children render immediately; props may be undefined initially -->
<Deferred :props="['users']">
    <UserList :users="users ?? []" />
</Deferred>
@endboostsnippet
@endif

Ensure your components handle the case where deferred props are initially `undefined` or `null`.
@endif

### Form `processing` reset timing

The `processing` state on Inertia forms now resets at a different point in the request lifecycle. If you depend on the exact timing of when `form.processing` becomes `false`, test your forms after upgrading.

### Testing concerns removed

The `Inertia::testing()` method and `TestingConcerns` trait have been removed. Use Inertia's built-in assertion methods instead:

@boostsnippet('Testing Migration', 'php')
// Before (v2)
use Inertia\Testing\TestingConcerns;

// After (v3) - use built-in assertions
$response = $this->get('/users');
$response->assertInertia(fn ($page) => $page
    ->component('Users/Index')
    ->has('users', 10)
);
@endboostsnippet

## Other changes

### SSR in development

Inertia v3 supports running the SSR server in development mode alongside Vite. This is now handled automatically when using `vite` dev server.

### Middleware priority

The Inertia middleware is now automatically registered at the correct priority. If you were manually configuring middleware priority, you can remove that customization.
@if($usesReact || $usesVue)

### Nested prop types

TypeScript users will notice improved type inference for nested page props. If you were using workarounds for nested types, you may be able to simplify them.
@endif

### ESM-only

The client-side packages are now ESM-only. If your build setup requires CommonJS, you'll need to update your bundler configuration to handle ESM imports.

## Getting help

If you encounter issues during the upgrade:

- Check the [documentation](https://inertiajs.com) for detailed feature guides
- Visit the [GitHub discussions](https://github.com/inertiajs/inertia/discussions) for community support
