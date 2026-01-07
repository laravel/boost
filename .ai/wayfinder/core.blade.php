@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Laravel Wayfinder

Wayfinder generates TypeScript functions and types for Laravel controllers and routes which you can import into your client-side code. It provides type safety and automatic synchronization between backend routes and frontend code.

### Development Guidelines
- Always use the `search-docs` tool to check Wayfinder correct usage before implementing any features.
- Always prefer named imports for tree-shaking (e.g., `import { show } from '@/actions/...'`).
- Avoid default controller imports (prevents tree-shaking).
- Run `{{ $assist->artisanCommand('wayfinder:generate') }}` after route changes if Vite plugin isn't installed.

### Feature Overview
- Form Support: Use `.form()` with `--with-form` flag for HTML form attributes — `<form {...store.form()}>` → `action="/posts" method="post"`.
- HTTP Methods: Call `.get()`, `.post()`, `.patch()`, `.put()`, `.delete()` for specific methods — `show.head(1)` → `{ url: "/posts/1", method: "head" }`.
- Invokable Controllers: Import and invoke directly as functions. For example, `import StorePost from '@/actions/.../StorePostController'; StorePost()`.
- Named Routes: Import from `@/routes/` for non-controller routes. For example, `import { show } from '@/routes/post'; show(1)` for route name `post.show`.
- Parameter Binding: Detects route keys (e.g., `{post:slug}`) and accepts matching object properties — `show("my-post")` or `show({ slug: "my-post" })`.
- Query Merging: Use `mergeQuery` to merge with `window.location.search`, set values to `null` to remove — `show(1, { mergeQuery: { page: 2, sort: null } })`.
- Query Parameters: Pass `{ query: {...} }` in options to append params — `show(1, { query: { page: 1 } })` → `"/posts/1?page=1"`.
- Route Objects: Functions return `{ url, method }` shaped objects — `show(1)` → `{ url: "/posts/1", method: "get" }`.
- URL Extraction: Use `.url()` to get URL string — `show.url(1)` → `"/posts/1"`.

### Example Usage
@verbatim
<code-snippet name="Wayfinder Basic Usage" lang="typescript">
    // Import controller methods (tree-shakable)...
    import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

    // Get route object with URL and method...
    show(1) // { url: "/posts/1", method: "get" }

    // Get just the URL...
    show.url(1) // "/posts/1"

    // Use specific HTTP methods...
    show.get(1) // { url: "/posts/1", method: "get" }
    show.head(1) // { url: "/posts/1", method: "head" }

    // Import named routes...
    import { show as postShow } from '@/routes/post' // For route name 'post.show'
    postShow(1) // { url: "/posts/1", method: "get" }
</code-snippet>
@endverbatim

@if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_LARAVEL) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_REACT) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_VUE) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
### Wayfinder + Inertia
@if($assist->inertia()->hasFormComponent())
If your application uses the `<Form>` component from Inertia, you can use Wayfinder to generate form action and method automatically.
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
@if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
@boostsnippet("Wayfinder Form Component (Svelte)", "svelte")
<Form {...store.form()}><input name="title" /></Form>
@endboostsnippet
@endif
@else
If your application uses the `useForm` component from Inertia, you can directly submit to the Wayfinder generated functions.

<code-snippet name="Wayfinder useForm Example" lang="typescript">
    import { store } from "@/actions/App/Http/Controllers/ExampleController";

    const form = useForm({
        name: "My Big Post",
    });

    form.submit(store());
</code-snippet>
@endif
@endif
