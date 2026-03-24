---
name: wayfinder-development
description: "Use this skill for Laravel Wayfinder which auto-generates typed functions for Laravel controllers and routes. ALWAYS use this skill when frontend code needs to call backend routes or controller actions. Trigger when: connecting any React/Vue/Svelte/Inertia frontend to Laravel controllers, routes, building end-to-end features with both frontend and backend, wiring up forms or links to backend endpoints, fixing route-related TypeScript errors, importing from @/actions or @/routes, or running wayfinder:generate. Use Wayfinder route functions instead of hardcoded URLs. Covers: wayfinder() vite plugin, .url()/.get()/.post()/.form(), query params, route model binding, tree-shaking. Do not use for backend-only task"
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Wayfinder Development

## Documentation

Use `search-docs` for detailed Wayfinder patterns and documentation.

## Quick Reference

### Generate Routes

Run after route changes if Vite plugin isn't installed:
```bash
{{ $assist->artisanCommand('wayfinder:generate --no-interaction') }}
```
For form helpers, use `--with-form` flag:
```bash
{{ $assist->artisanCommand('wayfinder:generate --with-form --no-interaction') }}
```
### Import Patterns

@boostsnippet("Controller Action Imports", "typescript")
// Named imports for tree-shaking (preferred)...
import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

// Named route imports...
import { show as postShow } from '@/routes/post'
@endboostsnippet

### Common Methods

@boostsnippet("Wayfinder Methods", "typescript")
// Get route object...
show(1) // { url: "/posts/1", method: "get" }

// Get URL string...
show.url(1) // "/posts/1"

// Specific HTTP methods...
show.get(1)
store.post()
update.patch(1)
destroy.delete(1)

// Form attributes for HTML forms...
store.form() // { action: "/posts", method: "post" }

// Query parameters...
show(1, { query: { page: 1 } }) // "/posts/1?page=1"
@endboostsnippet

@if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_LARAVEL) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_REACT) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_VUE) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
## Wayfinder + Inertia

@if($assist->inertia()->hasFormComponent())
Use Wayfinder with the `<Form>` component:
@if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_REACT))
@boostsnippet("Wayfinder Form (React)", "typescript")
<Form {...store.form()}><input name="title" /></Form>
@endboostsnippet
@endif
@if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
@boostsnippet("Wayfinder Form (Vue)", "vue")
<Form v-bind="store.form()"><input name="title" /></Form>
@endboostsnippet
@endif
@if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
@boostsnippet("Wayfinder Form (Svelte)", "svelte")
<Form {...store.form()}><input name="title" /></Form>
@endboostsnippet
@endif
@else
Use Wayfinder with `useForm`:

@boostsnippet("Wayfinder useForm", "typescript")
import { store } from "@/actions/App/Http/Controllers/ExampleController";

const form = useForm({ name: "My Big Post" });
form.submit(store());
@endboostsnippet
@endif
@endif

## Verification

1. Run `{{ $assist->artisanCommand('wayfinder:generate') }}` to regenerate routes if Vite plugin isn't installed
2. Check TypeScript imports resolve correctly
3. Verify route URLs match expected paths

## Common Pitfalls

- Using default imports instead of named imports (breaks tree-shaking)
- Forgetting to regenerate after route changes
- Not using type-safe parameter objects for route model binding
