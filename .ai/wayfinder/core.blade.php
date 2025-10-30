@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Laravel Wayfinder

Generates fully-typed TypeScript functions for Laravel controllers and routes. Import endpoints as functions—never hardcode URLs. Provides type safety and automatic synchronization between backend routes and frontend code.

### Setup

- Run `php artisan wayfinder:generate`, install `npm i -D @laravel/vite-plugin-wayfinder`, add `wayfinder()` to `vite.config.js`.
- Add `wayfinder/`, `actions/`, `routes/` to `.gitignore`.

### Development Guidelines

- **Always use `search-docs`** before implementing any Wayfinder features
- Prefer named imports for tree-shaking (e.g., `import { show } from '@/actions/...'`)
- Avoid default controller imports (prevents tree-shaking)
- Run `wayfinder:generate` after route changes if Vite plugin isn't installed

**Features:**

- **Route Objects**: Functions return `{ url, method }` objects — `show(1)` → `{ url: "/posts/1", method: "get" }`
- **URL Extraction**: Use `.url()` to get URL string — `show.url(1)` → `"/posts/1"`
- **HTTP Methods**: Call `.get()`, `.post()`, `.patch()`, `.put()`, `.delete()` for specific methods — `show.head(1)` → `{ url: "/posts/1", method: "head" }`
- **Parameter Binding**: Detects route keys (e.g., `{post:slug}`) and accepts matching object properties — `show("my-post")` or `show({ slug: "my-post" })`
- **Invokable Controllers**: Import and invoke directly as functions — `import StorePost from '@/actions/.../StorePostController'; StorePost()`
- **Named Routes**: Import from `@/routes/` for non-controller routes — `import { show } from '@/routes/post'; show(1)` for route name `post.show`
- **Form Support**: Use `.form()` with `--with-form` flag for HTML form attributes — `<form {...store.form()}>` → `action="/posts" method="post"`
- **Query Parameters**: Pass `{ query: {...} }` in options to append params — `show(1, { query: { page: 1 } })` → `"/posts/1?page=1"`
- **Query Merging**: Use `mergeQuery` to merge with `window.location.search`, set values to `null` to remove — `show(1, { mergeQuery: { page: 2, sort: null } })`

**Basic Usage:**
@verbatim
    <code-snippet lang="typescript" name="Wayfinder Basic Usage">
        // Import controller methods (tree-shakable)
        import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

        // Get route object with URL and method
        show(1) // { url: "/posts/1", method: "get" }

        // Get just the URL
        show.url(1) // "/posts/1"

        // Use specific HTTP methods
        show.get(1) // { url: "/posts/1", method: "get" }
        show.head(1) // { url: "/posts/1", method: "head" }

        // Import named routes
        import { show as postShow } from '@/routes/post' // For route name 'post.show'
        postShow(1) // { url: "/posts/1", method: "get" }
    </code-snippet>
@endverbatim

@if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_LARAVEL) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_REACT) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_VUE) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
### Wayfinder + Inertia
@if($assist->inertia()->hasFormComponent())
If your application uses the `<Form>` component, you can use Wayfinder to generate form attributes automatically.
    @if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_REACT))
        @boostsnippet("Wayfinder Form Component (React)", "typescript")
            <Form {...store.form()}><input name="title" /></Form>
        @endboostsnippet
   @endif
    @if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
        @boostsnippet("Wayfinder Form Component (Vue)", "vue")
            <Form v-bind="store.form()"><input name="title" /></Form>
        @endboostsnippet
    @endif
@endif
@endif

