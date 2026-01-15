---
name: inertia-svelte-development
description: >-
  Develops Inertia.js v2 Svelte client-side applications. Activates when creating
  Svelte pages, forms, or navigation; using Link, Form, or router; working with
  deferred props, prefetching, or polling; or when user mentions Svelte with Inertia,
  Svelte pages, Svelte forms, or Svelte navigation.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Inertia Svelte Development

## When to Apply

Activate this skill when:
- Creating or modifying Svelte page components for Inertia
- Working with forms in Svelte (using `<Form>` or `useForm`)
- Implementing client-side navigation with `<Link>` or `router`
- Using v2 features: deferred props, prefetching, or polling
- Building Svelte-specific features with the Inertia protocol

## Core Patterns

### Page Components Location

Svelte page components should be placed in the `resources/js/Pages` directory.

### Page Component Structure

<code-snippet name="Basic Svelte Page Component" lang="svelte">
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
</code-snippet>

### Documentation

Use the `search-docs` tool for accurate guidance on all things Inertia.

## Client-Side Navigation

### Basic Link Component

Use `<Link>` for client-side navigation instead of traditional `<a>` tags:

<code-snippet name="Inertia Svelte Navigation" lang="svelte">
<script>
import { Link } from '@inertiajs/svelte'
</script>

<Link href="/">Home</Link>
<Link href="/users">Users</Link>
<Link href={`/users/${user.id}`}>View User</Link>
</code-snippet>

### Link with Method

<code-snippet name="Link with POST Method" lang="svelte">
<script>
import { Link } from '@inertiajs/svelte'
</script>

<Link href="/logout" method="post">Logout</Link>
</code-snippet>

### Prefetching

Prefetch pages to improve perceived performance:

<code-snippet name="Prefetch on Hover" lang="svelte">
<script>
import { Link } from '@inertiajs/svelte'
</script>

<Link href="/users" prefetch>Users</Link>
</code-snippet>

### Programmatic Navigation

<code-snippet name="Router Visit" lang="svelte">
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
</code-snippet>

## Form Handling

@if($assist->inertia()->hasFormComponent())
### Form Component (Recommended)

The recommended way to build forms is with the `<Form>` component:

<code-snippet name="Form Component Example" lang="svelte">
<script>
import { Form } from '@inertiajs/svelte'
</script>

<Form action="/users" method="post" let:errors let:processing let:wasSuccessful>
    <input type="text" name="name" />
    {#if errors.name}
        <div>{errors.name}</div>
    {/if}

    <input type="email" name="email" />
    {#if errors.email}
        <div>{errors.email}</div>
    {/if}

    <button type="submit" disabled={processing}>
        {processing ? 'Creating...' : 'Create User'}
    </button>

    {#if wasSuccessful}
        <div>User created!</div>
    {/if}
</Form>
</code-snippet>

@if($assist->inertia()->hasFormComponentResets())
### Form Component Reset Props

The `<Form>` component supports automatic resetting:

- `resetOnError` - Reset form data when the request fails
- `resetOnSuccess` - Reset form data when the request succeeds
- `setDefaultsOnSuccess` - Update default values on success

Use the `search-docs` tool with a query of `form component resetting` for detailed guidance.

<code-snippet name="Form with Reset Props" lang="svelte">
<script>
import { Form } from '@inertiajs/svelte'
</script>

<Form
    action="/users"
    method="post"
    resetOnSuccess
    setDefaultsOnSuccess
    let:errors
    let:processing
    let:wasSuccessful
>
    <input type="text" name="name" />
    {#if errors.name}
        <div>{errors.name}</div>
    {/if}

    <button type="submit" disabled={processing}>
        Submit
    </button>
</Form>
</code-snippet>
@else
**Note:** This version of Inertia does not support `resetOnError`, `resetOnSuccess`, or `setDefaultsOnSuccess` on the `<Form>` component. Using these props will cause errors. Upgrade to Inertia v2.2.0+ to use these features.
@endif

Forms can also be built using the `useForm` hook for more programmatic control. Use the `search-docs` tool with a query of `useForm helper` for guidance.

@endif

### useForm Hook

@if($assist->inertia()->hasFormComponent() === false)
**For Inertia v2.0.x:** Build forms using the `useForm` hook as the `<Form>` component is not available until v2.1.0+.
@else
For more programmatic control or to follow existing conventions, use the `useForm` hook:
@endif

<code-snippet name="useForm Example" lang="svelte">
<script>
import { useForm } from '@inertiajs/svelte'

const form = useForm({
    name: '',
    email: '',
    password: '',
})

function submit() {
    $form.post('/users', {
        onSuccess: () => $form.reset('password'),
    })
}
</script>

<form on:submit|preventDefault={submit}>
    <input type="text" bind:value={$form.name} />
    {#if $form.errors.name}
        <div>{$form.errors.name}</div>
    {/if}

    <input type="email" bind:value={$form.email} />
    {#if $form.errors.email}
        <div>{$form.errors.email}</div>
    {/if}

    <input type="password" bind:value={$form.password} />
    {#if $form.errors.password}
        <div>{$form.errors.password}</div>
    {/if}

    <button type="submit" disabled={$form.processing}>
        Create User
    </button>
</form>
</code-snippet>

## Inertia v2 Features

### Deferred Props

Use deferred props to load data after initial page render:

<code-snippet name="Deferred Props with Empty State" lang="svelte">
<script>
export let users
</script>

<div>
    <h1>Users</h1>
    {#if !users}
        <div class="animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
        </div>
    {:else}
        <ul>
            {#each users as user (user.id)}
                <li>{user.name}</li>
            {/each}
        </ul>
    {/if}
</div>
</code-snippet>

### Polling

Automatically refresh data at intervals:

<code-snippet name="Polling Example" lang="svelte">
<script>
import { router } from '@inertiajs/svelte'
import { onMount, onDestroy } from 'svelte'

export let stats

let interval

onMount(() => {
    interval = setInterval(() => {
        router.reload({ only: ['stats'] })
    }, 5000) // Poll every 5 seconds
})

onDestroy(() => {
    clearInterval(interval)
})
</script>

<div>
    <h1>Dashboard</h1>
    <div>Active Users: {stats.activeUsers}</div>
</div>
</code-snippet>

## Server-Side Patterns

Server-side patterns (Inertia::render, props, middleware) are covered in inertia-laravel guidelines.

## Common Pitfalls

- Using traditional `<a>` links instead of Inertia's `<Link>` component (breaks SPA behavior)
- Forgetting to add loading states (skeleton screens) when using deferred props
- Not handling the `undefined` state of deferred props before data loads
- Using `<form>` without preventing default submission (use `<Form>` component or `on:submit|preventDefault`)
- Forgetting to check if `<Form>` component is available in your Inertia version
