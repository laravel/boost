# Path-Scoped Guideline Rules

Boost can extract path-scoped sections of a guideline into standalone rule files so
agents only load them when they touch matching files, instead of carrying every
guideline in context on every request.

## Syntax

Wrap any portion of a guideline file (`core.blade.php`, a versioned guideline, or a
plain `.md`) in a `@scoped([...])` / `@endscoped` block. The argument is an array of
glob patterns the block applies to:

```blade
# Do Things the Laravel Way

- Use Artisan `make:` commands to create new files.

@scoped(['app/Models/**'])
### Model Creation

- When creating new models, create useful factories and seeders for them too.
@endscoped

@scoped(['app/Http/**', 'routes/**'])
### APIs & Eloquent Resources

- Default to Eloquent API Resources and API versioning.
@endscoped
```

Blade expressions (`{{ $assist->artisanCommand('make:model') }}`) render normally
inside a block. Content outside any block stays inline in the composed guidelines.

### Showing a literal `@scoped` example

To document the directive itself without it being extracted, put the example inside a
fenced code block or a `@boostsnippet`. Directives inside either are treated as
literal text and left untouched:

    ```blade
    @scoped(['app/Models/**'])
    ...
    @endscoped
    ```

## Generated files and ownership

When rules are enabled, each block is written to a Boost-managed rule file:

- `.ai/rules/boost/*.md` — **Boost-owned**. These are wiped and regenerated on every
  `boost:install`; never edit them by hand. Blocks that share the exact same globs are
  merged into a single file.
- `.ai/rules/index.md` — a generated table mapping globs to rule files. Agents read it
  first, then open the rule file whose globs match the path they are editing.
- `.ai/rules/*.md` (repository root, outside `boost/`) — **your team's** rules, e.g.
  those recorded via the `record-rule` MCP tool. Boost never touches these.

The extracted content is removed from the inline guidelines, so it is not duplicated.

## Overrides

Overriding a guideline through `.ai/guidelines/<key>.blade.php` also overrides its
scoped blocks — your override's `@scoped` blocks replace the package defaults entirely.

## Third-party packages

Package authors can ship path-scoped guidelines from their own repository under
`resources/boost/guidelines/`. Every guideline file in that directory is discovered
(a package may ship more than one), and each is selectable during `boost:install`.

## The default-enabled toggle

Extraction is on by default. Control it with:

```php
// config/boost.php
'rules' => [
    'enabled' => env('BOOST_RULES_ENABLED', true),
],
```

When disabled (`BOOST_RULES_ENABLED=false`), Boost re-inlines every scoped block back
into the guidelines byte-for-byte and removes the `.ai/rules/boost` directory on the
next `boost:install`. If writing the managed files ever fails, Boost falls back to the
same inline behavior and warns, so guideline content is never lost.
