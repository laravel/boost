---
name: inertia-svelte-development
description: "Develops Inertia.js v1 Svelte client-side applications. Activates when creating Svelte pages, forms, or navigation; using Link or router; or when user mentions Svelte with Inertia, Svelte pages, Svelte forms, or Svelte navigation."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Inertia Svelte development

## Documentation

Use `search-docs` for detailed Inertia v1 Svelte patterns and documentation.

## Basic usage

### Page components location

Place Svelte page components in the `{{ $assist->inertia()->pagesDirectory() }}` directory.

### Page component structure

@boostsnippet("Basic Svelte Page Component", "svelte")
<script>
export let users
</script>

<div>
    <h1>Users</h1>
    <ul>
        {#each users as user (user.id)}
            <li>{user.name}</li>
        {/each}
    </ul>
</div>
@endboostsnippet

## Client-side navigation

### Basic Link component

Use `<Link>` for client-side navigation instead of `<a>` tags:

@boostsnippet("Inertia Svelte Navigation", "svelte")
<script>
import { Link } from '@inertiajs/svelte'
</script>

<Link href="/">Home</Link>
<Link href="/users">Users</Link>
<Link href={`/users/${user.id}`}>View User</Link>
@endboostsnippet

### Link with method

@boostsnippet("Link with POST Method", "svelte")
<script>
import { Link } from '@inertiajs/svelte'
</script>

<Link href="/logout" method="post">Logout</Link>
@endboostsnippet

### Programmatic navigation

@boostsnippet("Router Visit", "svelte")
<script>
import { router } from '@inertiajs/svelte'

function handleClick() {
    router.visit('/users')
}

// Or with options
function createUser() {
    router.visit('/users', {
        method: 'post',
        data: { name: 'John' },
        onSuccess: () => console.log('Success!'),
    })
}
</script>
@endboostsnippet

## Form handling

### Using `router.post`

@boostsnippet("Form with router.post", "svelte")
<script>
import { router } from '@inertiajs/svelte'

let form = {
    name: '',
    email: '',
}
let processing = false

function handleSubmit() {
    processing = true

    router.post('/users', form, {
        onFinish: () => processing = false,
    })
}
</script>

<form on:submit|preventDefault={handleSubmit}>
    <input type="text" bind:value={form.name} />
    <input type="email" bind:value={form.email} />
    <button type="submit" disabled={processing}>
        Create User
    </button>
</form>
@endboostsnippet

## Inertia v1 limitations

Inertia v1 does not support these v2 features:
- `<Form>` component
- Deferred props
- Prefetching
- Polling
- Infinite scrolling with `WhenVisible`
- Merging props

Do not use these features in v1 projects.

## Server-side patterns

The inertia-laravel guidelines cover server-side patterns such as Inertia::render, props, and middleware.

## Common pitfalls

- Using traditional `<a>` links instead of Inertia's `<Link>` component (breaks SPA behavior)
- Using Inertia v2 features, such as deferred props or the `<Form>` component, in v1 projects
- Using `<form>` without preventing default submission (use `on:submit|preventDefault`)
- Not handling loading states during form submission
