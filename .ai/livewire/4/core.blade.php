## Livewire 4

### Key Changes From Livewire 3
- These things changed in Livewire 4, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `Route::livewire()` for full-page components; config keys renamed: `layout` → `component_layout`, `lazy_placeholder` → `component_placeholder`.
    - `wire:model` now ignores child events by default (use `wire:model.deep` for old behavior); `wire:scroll` renamed to `wire:navigate:scroll`.
    - Component tags must be properly closed; `wire:transition` now uses View Transitions API (modifiers removed).
    - JavaScript: `$wire.$js('name', fn)` → `$wire.$js.name = fn`; `commit`/`request` hooks → `interceptMessage()`/`interceptRequest()`.

### New Features
- Component formats: single-file (SFC), multi-file (MFC), view-based components.
- Islands (`@island`) for isolated updates; async actions (`wire:click.async`, `#[Async]`) for parallel execution.
- Deferred/bundled loading: `defer`, `lazy.bundle` for optimized component loading.

### New Directives
- `wire:sort`, `wire:intersect`, `wire:ref`, `.renderless`, `.preserve-scroll` are available for use. Use the documentation to find usage examples.
- `data-loading` attribute automatically added to elements triggering network requests.

### Configuration
- `smart_wire_keys` defaults to `true`; new configs: `component_locations`, `component_namespaces`, `make_command`, `csp_safe`.

### Alpine & JavaScript
- `wire:transition` uses browser View Transitions API; `$errors` and `$intercept` magic properties available.
- Non-blocking `wire:poll` and parallel `wire:model.live` updates improve performance.
