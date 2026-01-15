# Livewire v3 to v4 Upgrade Specialist

You are an expert Livewire upgrade specialist with deep knowledge of both Livewire v3 and v4. Your task is to systematically upgrade the application from Livewire v3 to v4 while ensuring all functionality remains intact. You understand the nuances of breaking changes and can identify affected code patterns with precision.

## Core Principle: Documentation-First Approach

**IMPORTANT:** Always use the `search-docs` tool whenever you need:
- Specific code examples for implementing Livewire v4 features
- Clarification on breaking changes or new syntax
- Verification of upgrade patterns before applying them
- Examples of correct usage for new directives or methods

The official Livewire documentation is your primary source of truth. Consult it before making assumptions or implementing changes.

## Upgrade Process

Follow this systematic process to upgrade the application:

### 1. Assess Current State

Before making any changes:

- Check `composer.json` for the current Livewire version constraint
- Run `{{ $assist->composerCommand('show livewire/livewire') }}` to confirm installed version
- Identify all Livewire components in the application (search for `extends Component`)
- Review `config/livewire.php` for current configuration

### 2. Create Safety Net

- Ensure you're working on a dedicated branch
- Run the existing test suite to establish baseline
- Note any components with complex JavaScript interactions

### 3. Analyze Codebase for Breaking Changes

Search the codebase for patterns affected by v4 changes:

**High Priority Searches:**
- `config/livewire.php` - Configuration key renames needed
- `Route::get` with Livewire components - May need `Route::livewire()`
- `wire:model` on container elements (divs, modals) - Check for bubbling behavior
- `wire:scroll` - Needs rename to `wire:navigate:scroll`
- `<livewire:` tags - Must be properly closed (self-closing or with closing tag)

**Medium Priority Searches:**
- `wire:transition` with modifiers (`.opacity`, `.scale`, `.duration`) - Modifiers removed
- `$this->stream(` - Parameter order changed
- Array property replacements from JavaScript - Hook behavior changed

**Low Priority Searches:**
- `$wire.$js(` or `$js(` - Deprecated syntax
- `Livewire.hook('commit'` or `Livewire.hook('request'` - Deprecated hooks

### 4. Apply Changes Systematically

For each category of changes:

1. **Search** for affected patterns using grep/search tools
2. **Consult documentation** - Use `search-docs` tool to verify correct upgrade patterns and examples
3. **List** all files that need modification
4. **Apply** the fix consistently across all occurrences
5. **Verify** each change doesn't break functionality

### 5. Update Dependencies

After code changes are complete:

```bash
{{ $assist->composerCommand('require livewire/livewire:^4.0') }}
{{ $assist->artisanCommand('optimize:clear') }}
```

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

- Most applications can upgrade with minimal changes
- The old syntax for deprecated features still works but should be migrated

---

# Upgrading from v3 to v4

Livewire v4 introduces several improvements and optimizations while maintaining backward compatibility wherever possible. This guide will help you upgrade from Livewire v3 to v4.

> [!tip] Smooth upgrade path
> Most applications can upgrade to v4 with minimal changes. The breaking changes are primarily configuration updates and method signature changes that only affect advanced usage.

## Installation

Update your `composer.json` to require Livewire v4:

@boostsnippet('Installation', 'bash')
composer require livewire/livewire:^4.0
@endboostsnippet

After updating, clear your application's cache:

@boostsnippet('Clear Cache', 'bash')
php artisan optimize:clear
@endboostsnippet

> [!info] View all changes on GitHub
> For a complete overview of all code changes between v3 and v4, you can review the full diff on GitHub: [Compare 3.x to main →](https://github.com/livewire/livewire/compare/3.x...main)

## High-impact changes

These changes are most likely to affect your application and should be reviewed carefully.

### Config file updates

Several configuration keys have been renamed, reorganized, or have new defaults. Update your `config/livewire.php` file:

> [!tip] View the full config file
> For reference, you can view the complete v4 config file on GitHub: [livewire.php →](https://github.com/livewire/livewire/blob/main/config/livewire.php)

#### Renamed configuration keys

**Layout configuration:**

@boostsnippet('Layout Configuration', 'php')
// Before (v3)
'layout' => 'components.layouts.app',

// After (v4)
'component_layout' => 'layouts::app',
@endboostsnippet

The layout now uses the `layouts::` namespace by default, pointing to `resources/views/layouts/app.blade.php`.

**Placeholder configuration:**

@boostsnippet('Placeholder Configuration', 'php')
// Before (v3)
'lazy_placeholder' => 'livewire.placeholder',

// After (v4)
'component_placeholder' => 'livewire.placeholder',
@endboostsnippet

#### Changed defaults

**Smart wire:key behavior:**

@boostsnippet('Smart Wire Key', 'php')
// Now defaults to true (was false in v3)
'smart_wire_keys' => true,
@endboostsnippet

This helps prevent wire:key issues on deeply nested components. Note: You still need to add `wire:key` manually in loops—this setting doesn't eliminate that requirement.

[Learn more about wire:key →](/docs/4.x/nesting#rendering-children-in-a-loop)

#### New configuration options

**Component locations:**

@boostsnippet('Component Locations', 'php')
'component_locations' => [
    resource_path('views/components'),
    resource_path('views/livewire'),
],
@endboostsnippet

Defines where Livewire looks for single-file and multi-file (view-based) components.

**Component namespaces:**

@boostsnippet('Component Namespaces', 'php')
'component_namespaces' => [
    'layouts' => resource_path('views/layouts'),
    'pages' => resource_path('views/pages'),
],
@endboostsnippet

Creates custom namespaces for organizing view-based components (e.g., `<livewire:pages::dashboard />`).

**Make command defaults:**

@boostsnippet('Make Command Defaults', 'php')
'make_command' => [
    'type' => 'sfc',  // Options: 'sfc', 'mfc', or 'class'
    'emoji' => true,   // Whether to use ⚡ emoji prefix
],
@endboostsnippet

Configure default component format and emoji usage. Set `type` to `'class'` to match v3 behavior.

**CSP-safe mode:**

@boostsnippet('CSP Safe Mode', 'php')
'csp_safe' => false,
@endboostsnippet

Enable Content Security Policy mode to avoid `unsafe-eval` violations. When enabled, Livewire uses the [Alpine CSP build](https://alpinejs.dev/advanced/csp). Note: This mode restricts complex JavaScript expressions in directives like `wire:click="addToCart($event.detail.productId)"` or global references like `window.location`.

### Routing changes

For full-page components, the recommended routing approach has changed:

@boostsnippet('Routing Changes', 'php')
// Before (v3) - still works but not recommended
Route::get('/dashboard', Dashboard::class);

// After (v4) - recommended for all component types
Route::livewire('/dashboard', Dashboard::class);

// For view-based components, you can use the component name
Route::livewire('/dashboard', 'pages::dashboard');
@endboostsnippet

Using `Route::livewire()` is now the preferred method and is required for single-file and multi-file components to work correctly as full-page components.

[Learn more about routing →](/docs/4.x/components#page-components)

### `wire:model` now ignores child events by default

In v3, `wire:model` would respond to input/change events that bubbled up from child elements. This caused unexpected behavior when using `wire:model` on container elements (like modals or accordions) that contained form inputs—clearing an input inside would bubble up and potentially close the modal.

In v4, `wire:model` now only listens for events originating directly on the element itself (equivalent to the `.self` modifier behavior).

If you have code that relies on capturing events from child elements, add the `.deep` modifier:

@boostsnippet('Wire Model Deep', 'blade')
<!-- Before (v3) - listened to child events by default -->
<div wire:model="value">
    <input type="text">
</div>

<!-- After (v4) - add .deep to restore old behavior -->
<div wire:model.deep="value">
    <input type="text">
</div>
@endboostsnippet

> [!tip] Most apps won't need changes
> This change primarily affects non-standard uses of `wire:model` on container elements. Standard form input bindings (inputs, selects, textareas) are unaffected.

### Use `wire:navigate:scroll`

When using `wire:scroll` to preserve scroll in a scrollable container across `wire:navigate` requests in v3, you will need to instead use `wire:navigate:scroll` in v4:

@boostsnippet('Wire Navigate Scroll', 'blade')
@@persist('sidebar')
    <div class="overflow-y-scroll" wire:scroll> <!-- [tl! remove] -->
    <div class="overflow-y-scroll" wire:navigate:scroll> <!-- [tl! add] -->
        <!-- ... -->
    </div>
@@endpersist
@endboostsnippet

### Component tags must be closed

In v3, Livewire component tags would render even without being properly closed. In v4, with the addition of slot support, component tags must be properly closed—otherwise Livewire interprets subsequent content as slot content and the component won't render:

@boostsnippet('Component Tags Closed', 'blade')
<!-- Before (v3) - unclosed tag -->
<livewire:component-name>

<!-- After (v4) - Self-closing tag -->
<livewire:component-name />
@endboostsnippet

[Learn more about rendering components →](/docs/4.x/components#rendering-components)

[Learn more about slots →](/docs/4.x/nesting#slots)

## Medium-impact changes

These changes may affect certain parts of your application depending on which features you use.

### `wire:transition` now uses View Transitions API

In v3, `wire:transition` was a wrapper around Alpine's `x-transition` directive, supporting modifiers like `.opacity`, `.scale`, `.duration.200ms`, and `.origin.top`.

In v4, `wire:transition` uses the browser's native [View Transitions API](https://developer.mozilla.org/en-US/docs/Web/API/View_Transitions_API) instead. Basic usage still works—elements will fade in and out smoothly—but all modifiers have been removed.

@boostsnippet('Wire Transition', 'blade')
<!-- This still works in v4 -->
<div wire:transition>...</div>

<!-- These modifiers are no longer supported -->
<div wire:transition.opacity>...</div> <!-- [tl! remove] -->
<div wire:transition.scale.origin.top>...</div> <!-- [tl! remove] -->
<div wire:transition.duration.500ms>...</div> <!-- [tl! remove] -->
@endboostsnippet

[Learn more about wire:transition →](/docs/4.x/wire-transition)

### Performance improvements

Livewire v4 includes significant performance improvements to the request handling system:

- **Non-blocking polling**: `wire:poll` no longer blocks other requests or is blocked by them
- **Parallel live updates**: `wire:model.live` requests now run in parallel, allowing faster typing and quicker results

These improvements happen automatically—no changes needed to your code.

### Update hooks consolidate array/object changes

When replacing an entire array or object from the frontend (e.g., `$wire.items = ['new', 'values']`), Livewire now sends a single consolidated update instead of granular updates for each index.

**Before:** Setting `$wire.items = ['a', 'b']` on an array of 4 items would fire `updatingItems`/`updatedItems` hooks multiple times—once for each index change plus `__rm__` removals.

**After:** The same operation fires the hooks once with the full new array value, matching v2 behavior.

If your code relies on individual index hooks firing when replacing entire arrays, you may need to adjust. Single-item changes (like `wire:model="items.0"`) still fire granular hooks as expected.

### Method signature changes

If you're extending Livewire's core functionality or using these methods directly, note these signature changes:

**Streaming:**

The `stream()` method parameter order has changed:

@boostsnippet('Stream Method Signature', 'php')
// Before (v3)
$this->stream(to: '#container', content: 'Hello', replace: true);

// After (v4)
$this->stream(content: 'Hello', replace: true, el: '#container');
@endboostsnippet

If you're using named parameters (as shown above), note that `to:` has been renamed to `el:`. If you're using positional parameters, you'll need to update to the following:

@boostsnippet('Stream Positional Parameters', 'php')
// Before (v3) - positional parameters
$this->stream('#container', 'Hello');

// After (v4) - positional/named parameters
$this->stream('Hello', el: '#container');
@endboostsnippet

[Learn more about streaming →](/docs/4.x/wire-stream)

**Component mounting (internal):**

If you're extending `LivewireManager` or calling the `mount()` method directly:

@boostsnippet('Mount Method Signature', 'php')
// Before (v3)
public function mount($name, $params = [], $key = null)

// After (v4)
public function mount($name, $params = [], $key = null, $slots = [])
@endboostsnippet

This change adds support for passing slots when mounting components and generally won't affect most applications.

## Low-impact changes

These changes only affect applications using advanced features or customization.

### JavaScript deprecations

#### Deprecated: `$wire.$js()` method

The `$wire.$js()` method for defining JavaScript actions has been deprecated:

@boostsnippet('Wire JS Deprecation', 'js')
// Deprecated (v3)
$wire.$js('bookmark', () => {
    // Toggle bookmark...
})

// New (v4)
$wire.$js.bookmark = () => {
    // Toggle bookmark...
}
@endboostsnippet

The new syntax is cleaner and more intuitive.

#### Deprecated: `$js` without prefix

The use of `$js` in scripts without `$wire.$js` or `this.$js` prefix has been deprecated:

@boostsnippet('JS Without Prefix Deprecation', 'js')
// Deprecated (v3)
$js('bookmark', () => {
    // Toggle bookmark...
})

// New (v4)
$wire.$js.bookmark = () => {
    // Toggle bookmark...
}
// Or
this.$js.bookmark = () => {
    // Toggle bookmark...
}
@endboostsnippet

> [!tip] Old syntax still works
> Both `$wire.$js('bookmark', ...)` and `$js('bookmark', ...)` will continue to work in v4 for backward compatibility, but you should migrate to the new syntax when convenient.

#### Deprecated: `commit` and `request` hooks

The `commit` and `request` hooks have been deprecated in favor of a new interceptor system that provides more granular control and better performance.

> [!tip] Old hooks still work
> The deprecated hooks will continue to work in v4 for backward compatibility, but you should migrate to the new system when convenient.

#### Migrating from `commit` hook

The old `commit` hook:

@boostsnippet('Old Commit Hook', 'js')
// OLD - Deprecated
Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
    respond(() => {
        // Runs after response received but before processing
    })

    succeed(({ snapshot, effects }) => {
        // Runs after successful response
    })

    fail(() => {
        // Runs if request failed
    })
})
@endboostsnippet

Should be replaced with the new `interceptMessage`:

@boostsnippet('New Intercept Message', 'js')
// NEW - Recommended
Livewire.interceptMessage(({ component, message, onFinish, onSuccess, onError, onFailure }) => {
    onFinish(() => {
        // Equivalent to respond()
    })

    onSuccess(({ payload }) => {
        // Equivalent to succeed()
        // Access snapshot via payload.snapshot
        // Access effects via payload.effects
    })

    onError(() => {
        // Equivalent to fail() for server errors
    })

    onFailure(() => {
        // Equivalent to fail() for network errors
    })
})
@endboostsnippet

#### Migrating from `request` hook

The old `request` hook:

@boostsnippet('Old Request Hook', 'js')
// OLD - Deprecated
Livewire.hook('request', ({ url, options, payload, respond, succeed, fail }) => {
    respond(({ status, response }) => {
        // Runs when response received
    })

    succeed(({ status, json }) => {
        // Runs on successful response
    })

    fail(({ status, content, preventDefault }) => {
        // Runs on failed response
    })
})
@endboostsnippet

Should be replaced with the new `interceptRequest`:

@boostsnippet('New Intercept Request', 'js')
// NEW - Recommended
Livewire.interceptRequest(({ request, onResponse, onSuccess, onError, onFailure }) => {
    // Access url via request.uri
    // Access options via request.options
    // Access payload via request.payload

    onResponse(({ response }) => {
        // Equivalent to respond()
        // Access status via response.status
    })

    onSuccess(({ response, responseJson }) => {
        // Equivalent to succeed()
        // Access status via response.status
        // Access json via responseJson
    })

    onError(({ response, responseBody, preventDefault }) => {
        // Equivalent to fail() for server errors
        // Access status via response.status
        // Access content via responseBody
    })

    onFailure(({ error }) => {
        // Equivalent to fail() for network errors
    })
})
@endboostsnippet

#### Key differences

1. **More granular error handling**: The new system separates network failures (`onFailure`) from server errors (`onError`)
2. **Better lifecycle hooks**: Message interceptors provide additional hooks like `onSync`, `onMorph`, and `onRender`
3. **Cancellation support**: Both messages and requests can be cancelled/aborted
4. **Component scoping**: Message interceptors can be scoped to specific components using `$wire.intercept(...)`

For complete documentation on the new interceptor system, see the [JavaScript Interceptors documentation](/docs/4.x/javascript#interceptors).

## Upgrading Volt

Livewire v4 now supports single-file components, which use the same syntax as Volt class-based components. This means you can migrate from Volt to Livewire's built-in single-file components.

### Update component imports

Replace all instances of `Livewire\Volt\Component` with `Livewire\Component`:

@boostsnippet('Volt Import Update', 'php')
// Before (Volt)
use Livewire\Volt\Component;

new class extends Component { ... }

// After (Livewire v4)
use Livewire\Component;

new class extends Component { ... }
@endboostsnippet

### Remove Volt service provider

Delete the Volt service provider file:

@boostsnippet('Remove Volt Provider', 'bash')
rm app/Providers/VoltServiceProvider.php
@endboostsnippet

Then remove it from the providers array in `bootstrap/providers.php`:

@boostsnippet('Update Providers Array', 'php')
// Before
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\VoltServiceProvider::class,
];

// After
return [
    App\Providers\AppServiceProvider::class,
];
@endboostsnippet

### Remove Volt package

Uninstall the Volt package:

@boostsnippet('Uninstall Volt', 'bash')
composer remove livewire/volt
@endboostsnippet

### Install Livewire v4

After completing the above changes, install Livewire v4. Your existing Volt class-based components will work without modification since they use the same syntax as Livewire's single-file components.

## New features in v4

Livewire v4 introduces several powerful new features you can start using immediately:

### Component features

**Single-file and multi-file components**

v4 introduces new component formats alongside the traditional class-based approach. Single-file components combine PHP and Blade in one file, while multi-file components organize PHP, Blade, JavaScript, and tests in a directory.

By default, view-based component files are prefixed with a ⚡ emoji to distinguish them from regular Blade files in your editor and searches. This can be disabled via the `make_command.emoji` config.

@boostsnippet('Make Livewire Commands', 'bash')
php artisan make:livewire create-post        # Single-file (default)
php artisan make:livewire create-post --mfc  # Multi-file
php artisan livewire:convert create-post     # Convert between formats
@endboostsnippet

[Learn more about component formats →](/docs/4.x/components)

**Slots and attribute forwarding**

Components now support slots and automatic attribute bag forwarding using `@{{ $attributes }}`, making component composition more flexible.

[Learn more about nesting components →](/docs/4.x/nesting)

**JavaScript in view-based components**

View-based components can now include `<script>` tags without the `@@script` wrapper. These scripts are served as separate cached files for better performance and automatic `$wire` binding:

@boostsnippet('JavaScript in Components', 'blade')
<div>
    <!-- Your component template -->
</div>

<script>
    // $wire is automatically bound as 'this'
    this.count++  // Same as $wire.count++

    // $wire is still available if preferred
    $wire.save()
</script>
@endboostsnippet

[Learn more about JavaScript in components →](/docs/4.x/javascript)

### Islands

Islands allow you to create isolated regions within a component that update independently, dramatically improving performance without creating separate child components.

@boostsnippet('Islands Example', 'blade')
@@island(name: 'stats', lazy: true)
    <div>@{{ $this->expensiveStats }}</div>
@@endisland
@endboostsnippet

[Learn more about islands →](/docs/4.x/islands)

### Loading improvements

**Deferred loading**

In addition to lazy loading (viewport-based), components can now be deferred to load immediately after the initial page load:

@boostsnippet('Deferred Loading Blade', 'blade')
<livewire:revenue defer />
@endboostsnippet

@boostsnippet('Deferred Loading PHP', 'php')
#[Defer]
class Revenue extends Component { ... }
@endboostsnippet

**Bundled loading**

Control whether multiple lazy/deferred components load in parallel or bundled together:

@boostsnippet('Bundled Loading Blade', 'blade')
<livewire:revenue lazy.bundle />
<livewire:expenses defer.bundle />
@endboostsnippet

@boostsnippet('Bundled Loading PHP', 'php')
#[Lazy(bundle: true)]
class Revenue extends Component { ... }
@endboostsnippet

[Learn more about lazy and deferred loading →](/docs/4.x/lazy)

### Async actions

Run actions in parallel without blocking other requests using the `.async` modifier or `#[Async]` attribute:

@boostsnippet('Async Actions Blade', 'blade')
<button wire:click.async="logActivity">Track</button>
@endboostsnippet

@boostsnippet('Async Actions PHP', 'php')
#[Async]
public function logActivity() { ... }
@endboostsnippet

[Learn more about async actions →](/docs/4.x/actions#parallel-execution-with-async)

### New directives and modifiers

**`wire:sort` - Drag-and-drop sorting**

Built-in support for sortable lists with drag-and-drop:

@boostsnippet('Wire Sort', 'blade')
<ul wire:sort="updateOrder">
    @@foreach ($items as $item)
        <li wire:sort:item="@{{ $item->id }}" wire:key="@{{ $item->id }}">@{{ $item->name }}</li>
    @@endforeach
</ul>
@endboostsnippet

[Learn more about wire:sort →](/docs/4.x/wire-sort)

**`wire:intersect` - Viewport intersection**

Run actions when elements enter or leave the viewport, similar to Alpine's [`x-intersect`](https://alpinejs.dev/plugins/intersect):

@boostsnippet('Wire Intersect', 'blade')
<!-- Basic usage -->
<div wire:intersect="loadMore">...</div>

<!-- With modifiers -->
<div wire:intersect.once="trackView">...</div>
<div wire:intersect:leave="pauseVideo">...</div>
<div wire:intersect.half="loadMore">...</div>
<div wire:intersect.full="startAnimation">...</div>

<!-- With options -->
<div wire:intersect.margin.200px="loadMore">...</div>
<div wire:intersect.threshold.50="trackScroll">...</div>
@endboostsnippet

Available modifiers:
- `.once` - Fire only once
- `.half` - Wait until half is visible
- `.full` - Wait until fully visible
- `.threshold.X` - Custom visibility percentage (0-100)
- `.margin.Xpx` or `.margin.X%` - Intersection margin

[Learn more about wire:intersect →](/docs/4.x/wire-intersect)

**`wire:ref` - Element references**

Easily reference and interact with elements in your template:

@boostsnippet('Wire Ref', 'blade')
<div wire:ref="modal">
    <!-- Modal content -->
</div>

<button wire:click="$js.scrollToModal">Scroll to modal</button>

<script>
    this.$js.scrollToModal = () => {
        this.$refs.modal.scrollIntoView()
    }
</script>
@endboostsnippet

[Learn more about wire:ref →](/docs/4.x/wire-ref)

**`.renderless` modifier**

Skip component re-rendering directly from the template:

@boostsnippet('Renderless Modifier', 'blade')
<button wire:click.renderless="trackClick">Track</button>
@endboostsnippet

This is an alternative to the `#[Renderless]` attribute for actions that don't need to update the UI.

**`.preserve-scroll` modifier**

Preserve scroll position during updates to prevent layout jumps:

@boostsnippet('Preserve Scroll', 'blade')
<button wire:click.preserve-scroll="loadMore">Load More</button>
@endboostsnippet

**`data-loading` attribute**

Every element that triggers a network request automatically receives a `data-loading` attribute, making it easy to style loading states with Tailwind:

@boostsnippet('Data Loading', 'blade')
<button wire:click="save" class="data-loading:opacity-50 data-loading:pointer-events-none">
    Save Changes
</button>
@endboostsnippet

[Learn more about loading states →](/docs/4.x/loading-states)

### JavaScript improvements

**`$errors` magic property**

Access your component's error bag from JavaScript:

@boostsnippet('Errors Magic Property', 'blade')
<div wire:show="$errors.has('email')">
    <span wire:text="$errors.first('email')"></span>
</div>
@endboostsnippet

[Learn more about validation →](/docs/4.x/validation)

**`$intercept` magic**

Intercept and modify Livewire requests from JavaScript:

@boostsnippet('Intercept Magic', 'blade')
<script>
this.$intercept('save', ({ ... }) => {
    // ...
})
</script>
@endboostsnippet

[Learn more about JavaScript interceptors →](/docs/4.x/javascript#interceptors)

**Island targeting from JavaScript**

Trigger island renders directly from the template:

@boostsnippet('Island Targeting', 'blade')
<button wire:click="loadMore" wire:island.append="stats">
    Load more
</button>
@endboostsnippet

[Learn more about islands →](/docs/4.x/islands)

## Getting help

If you encounter issues during the upgrade:

- Check the [documentation](https://livewire.laravel.com) for detailed feature guides
- Visit the [GitHub discussions](https://github.com/livewire/livewire/discussions) for community support
