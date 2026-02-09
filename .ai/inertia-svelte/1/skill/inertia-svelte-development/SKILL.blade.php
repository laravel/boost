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
# Inertia Svelte Development

## When to Apply

Activate this skill when:

- Creating or modifying Svelte page components for Inertia
- Working with forms in Svelte (using `router.post`)
- Implementing client-side navigation with `<Link>` or `router`
- Building Svelte-specific features with the Inertia protocol

## Documentation

Use `search-docs` for detailed Inertia v1 Svelte patterns and documentation.

## Basic Usage

### Page Components Location

Svelte page components should be placed in the `{{ $assist->inertia()->pagesDirectory() }}` directory.

### Page Component Structure

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

## Client-Side Navigation

### Basic Link Component

Use `<Link>` for client-side navigation instead of traditional `<a>` tags:

@boostsnippet("Inertia Svelte Navigation", "svelte")
<script>
import { Link } from '@inertiajs/svelte'
</script>

<Link href="/">Home</Link>
<Link href="/users">Users</Link>
<Link href={`/users/${user.id}`}>View User</Link>
@endboostsnippet

### Link with Method

@boostsnippet("Link with POST Method", "svelte")
<script>
import { Link } from '@inertiajs/svelte'
</script>

<Link href="/logout" method="post">Logout</Link>
@endboostsnippet

### Programmatic Navigation

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

## Form Handling

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

## Inertia v1 Limitations

Inertia v1 does not support these v2 features:
- `<Form>` component
- Deferred props
- Prefetching
- Polling
- Infinite scrolling with `WhenVisible`
- Merging props

Do not use these features in v1 projects.

## Server-Side Patterns

Server-side patterns (Inertia::render, props, middleware) are covered in inertia-laravel guidelines.

## Common Pitfalls

- Using traditional `<a>` links instead of Inertia's `<Link>` component (breaks SPA behavior)
- Trying to use Inertia v2 features (deferred props, `<Form>` component, etc.) in v1 projects
- Using `<form>` without preventing default submission (use `on:submit|preventDefault`)
- Not handling loading states during form submission
