# Blade and View Best Practices

## Components

Put `{{ $attributes->merge(['class' => 'alert alert-'.$type]) }}` on the root element so callers can add attributes; `class` values are appended, not replaced. Prefer a component when the interface needs props, an attribute bag, or slots. An include suits a small partial that intentionally uses current view data; pass an explicit array when implicit sharing would hide dependencies.

Use `@aware` when a nested component needs a prop passed to an ancestor. It does not expose the ancestor's default value unless it was passed via the attribute bag.

## Use `@pushOnce` for Per-Component Scripts

A repeated component duplicates its `@push` content on every render. Use a consistently named `@pushOnce` to emit it once per response.

## Share Data with a View Composer

Use a composer for data needed whenever specific named views render. Keep it compatible with every view it targets and avoid broad wildcards. It runs only for rendered views, not JSON or streamed responses.

## Return Blade Fragments for Partial Rendering

For htmx or Turbo, return a fragment of the same view: `view('dashboard', compact('users'))->fragmentIf($request->hasHeader('HX-Request'), 'user-list')`.
