---
name: inertia-svelte-development
description: "Develops Inertia.js v2 Svelte client-side applications. Activates when creating Svelte pages, forms, or navigation; using Link, Form, or router; working with deferred props, prefetching, or polling; or when user mentions Svelte with Inertia, Svelte pages, Svelte forms, or Svelte navigation."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Inertia Svelte development

## Documentation

Use `search-docs` for detailed Inertia v2 Svelte patterns and documentation.

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

@boostsnippet("Link With POST Method", "svelte")
<script>
import { Link } from '@inertiajs/svelte'
</script>

<Link href="/logout" method="post">Logout</Link>
@endboostsnippet

### Prefetching

Prefetch pages to improve perceived performance:

@boostsnippet("Prefetch on Hover", "svelte")
<script>
import { Link } from '@inertiajs/svelte'
</script>

<Link href="/users" prefetch>Users</Link>
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

@if($assist->inertia()->hasFormComponent())
### Form component (recommended)

The recommended way to build forms is with the `<Form>` component:

@boostsnippet("Form Component Example", "svelte")
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
@endboostsnippet

@if($assist->inertia()->hasFormComponentResets())
### Form component reset props

The `<Form>` component supports automatic resetting:

- `resetOnError`: reset form data when the request fails
- `resetOnSuccess`: reset form data when the request succeeds
- `setDefaultsOnSuccess`: update default values on success

For details, run the `search-docs` tool with the query `form component resetting`.

@boostsnippet("Form With Reset Props", "svelte")
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
@endboostsnippet
@else
Note: This version of Inertia does not support `resetOnError`, `resetOnSuccess`, or `setDefaultsOnSuccess` on the `<Form>` component. Using these props causes errors. To use these props, upgrade to Inertia v2.2.0+.
@endif

For more programmatic control, you can also build forms with the `useForm` hook. For guidance, run the `search-docs` tool with the query `useForm helper`.

@endif

### `useForm` hook

@if($assist->inertia()->hasFormComponent() === false)
In Inertia v2.0.x, build forms with the `useForm` hook. The `<Form>` component is not available until v2.1.0+.
@else
For more programmatic control or to follow existing conventions, use the `useForm` hook:
@endif

@boostsnippet("useForm Example", "svelte")
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
@endboostsnippet

## Inertia v2 features

### Deferred props

Use deferred props to load data after the initial page render:

@boostsnippet("Deferred Props with Empty State", "svelte")
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
@endboostsnippet

### Polling

Use the `usePoll` hook to refresh data automatically at intervals. The hook cleans up on unmount and throttles polling when the browser tab is inactive.

@boostsnippet("Basic Polling", "svelte")
<script>
import { usePoll } from '@inertiajs/svelte'

export let stats

usePoll(5000)
</script>

<div>
    <h1>Dashboard</h1>
    <div>Active Users: {stats.activeUsers}</div>
</div>
@endboostsnippet

@boostsnippet("Polling With Request Options and Manual Control", "svelte")
<script>
import { usePoll } from '@inertiajs/svelte'

export let stats

const { start, stop } = usePoll(5000, {
    only: ['stats'],
    onStart() {
        console.log('Polling request started')
    },
    onFinish() {
        console.log('Polling request finished')
    },
}, {
    autoStart: false,
    keepAlive: true,
})
</script>

<div>
    <h1>Dashboard</h1>
    <div>Active Users: {stats.activeUsers}</div>
    <button on:click={start}>Start Polling</button>
    <button on:click={stop}>Stop Polling</button>
</div>
@endboostsnippet

- `autoStart` (default `true`): set to `false` to start polling manually with the returned `start()` function
- `keepAlive` (default `false`): set to `true` to prevent throttling when the browser tab is inactive

## Server-side patterns

The inertia-laravel guidelines cover server-side patterns such as Inertia::render, props, and middleware.

## Common pitfalls

- Using traditional `<a>` links instead of Inertia's `<Link>` component (breaks SPA behavior)
- Forgetting to add loading states (skeleton screens) when using deferred props
- Not handling the `undefined` state of deferred props before data loads
- Using `<form>` without preventing default submission (use the `<Form>` component or `on:submit|preventDefault`)
- Forgetting to check whether the `<Form>` component is available in your Inertia version
