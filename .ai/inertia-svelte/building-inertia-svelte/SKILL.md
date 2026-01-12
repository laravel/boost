---
name: building-inertia-svelte
description: Building Inertia.js applications with Svelte. Use when creating Svelte components, managing state, handling forms, or integrating with Laravel backend.
---

# Building Inertia Svelte Apps

## When to use this skill

Use this skill when the user asks about:
- Creating Svelte components for Inertia apps
- Svelte stores and reactivity with Inertia
- Form handling with Svelte and Inertia
- TypeScript with Inertia Svelte

## Page Components

Basic page component:

```svelte
<!-- resources/js/Pages/Users/Index.svelte -->
<script>
    import { inertia, Link } from '@inertiajs/svelte'
    import Layout from '@/Layouts/AppLayout.svelte'

    export let users
    export let filters
</script>

<Layout>
    <svelte:head>
        <title>Users</title>
    </svelte:head>

    <h1>Users</h1>

    <Link href={route('users.create')} class="btn">
        Create User
    </Link>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {#each users.data as user (user.id)}
                <tr>
                    <td>{user.name}</td>
                    <td>{user.email}</td>
                    <td>
                        <Link href={route('users.edit', user.id)}>Edit</Link>
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</Layout>
```

## Layouts

Persistent layouts:

```svelte
<!-- resources/js/Layouts/AppLayout.svelte -->
<script>
    import { Link, page } from '@inertiajs/svelte'

    $: auth = $page.props.auth
    $: flash = $page.props.flash
</script>

<div class="min-h-screen">
    <nav>
        <Link href="/">Home</Link>
        <Link href="/users">Users</Link>
        {#if auth.user}
            <Link href="/logout" method="post" as="button">Logout</Link>
        {:else}
            <Link href="/login">Login</Link>
        {/if}
    </nav>

    {#if flash.success}
        <div class="alert alert-success">{flash.success}</div>
    {/if}

    <main>
        <slot />
    </main>
</div>
```

Set layout on page:
```svelte
<script context="module">
    import Layout from '@/Layouts/AppLayout.svelte'
    export const layout = Layout
</script>
```

## Forms with useForm

```svelte
<script>
    import { useForm } from '@inertiajs/svelte'

    let form = useForm({
        name: '',
        email: '',
        role: 'user',
    })

    function submit() {
        $form.post(route('users.store'), {
            onSuccess: () => $form.reset(),
            preserveScroll: true,
        })
    }
</script>

<form on:submit|preventDefault={submit}>
    <div>
        <label>Name</label>
        <input
            type="text"
            bind:value={$form.name}
            class:error={$form.errors.name}
        />
        {#if $form.errors.name}
            <span class="error">{$form.errors.name}</span>
        {/if}
    </div>

    <div>
        <label>Email</label>
        <input type="email" bind:value={$form.email} />
        {#if $form.errors.email}
            <span class="error">{$form.errors.email}</span>
        {/if}
    </div>

    <div>
        <label>Role</label>
        <select bind:value={$form.role}>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
    </div>

    <button type="submit" disabled={$form.processing}>
        {$form.processing ? 'Saving...' : 'Create User'}
    </button>
</form>
```

## Edit Forms

```svelte
<script>
    import { useForm } from '@inertiajs/svelte'

    export let user

    let form = useForm({
        name: user.name,
        email: user.email,
    })

    function submit() {
        $form.put(route('users.update', user.id))
    }
</script>

<form on:submit|preventDefault={submit}>
    <!-- Form fields -->
</form>
```

## Delete with Confirmation

```svelte
<script>
    import { useForm } from '@inertiajs/svelte'

    export let user

    let deleteForm = useForm({})

    function destroy() {
        if (confirm('Are you sure?')) {
            $deleteForm.delete(route('users.destroy', user.id))
        }
    }
</script>

<button on:click={destroy} disabled={$deleteForm.processing}>
    Delete
</button>
```

## Page Store

Access shared data:

```svelte
<script>
    import { page } from '@inertiajs/svelte'

    $: user = $page.props.auth.user
    $: flash = $page.props.flash
</script>

{#if user}
    <span>Welcome, {user.name}</span>
{:else}
    <Link href="/login">Login</Link>
{/if}
```

## Router for Navigation

```svelte
<script>
    import { router } from '@inertiajs/svelte'

    function navigateToUsers() {
        router.visit('/users')
    }

    function searchUsers(query) {
        router.get(route('users.index'), { search: query }, {
            preserveState: true,
            preserveScroll: true,
            only: ['users'],
        })
    }

    function deleteUser(id) {
        router.delete(route('users.destroy', id), {
            onSuccess: () => console.log('Deleted!'),
        })
    }
</script>
```

## File Uploads

```svelte
<script>
    import { useForm } from '@inertiajs/svelte'

    let form = useForm({
        name: '',
        avatar: null,
    })

    function handleFileChange(e) {
        $form.avatar = e.target.files[0]
    }

    function submit() {
        $form.post(route('profile.update'), {
            forceFormData: true,
        })
    }
</script>

<form on:submit|preventDefault={submit}>
    <input type="text" bind:value={$form.name} />
    <input type="file" on:change={handleFileChange} />

    {#if $form.progress}
        <progress value={$form.progress.percentage} max="100">
            {$form.progress.percentage}%
        </progress>
    {/if}

    <button type="submit" disabled={$form.processing}>Upload</button>
</form>
```

## Reactive Search

```svelte
<script>
    import { router } from '@inertiajs/svelte'
    import debounce from 'lodash/debounce'

    export let users
    export let filters

    let search = filters.search || ''

    const debouncedSearch = debounce((value) => {
        router.get(route('users.index'), { search: value }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)

    $: debouncedSearch(search)
</script>

<input type="text" bind:value={search} placeholder="Search users..." />
```

## Best Practices

1. **Use Svelte stores** - `$form` syntax for reactive forms
2. **Persistent layouts** - Export layout in context module
3. **Reactive statements** - Use `$:` for derived values
4. **Two-way binding** - `bind:value` for form inputs
5. **Event modifiers** - `on:submit|preventDefault`
6. **Keyed each blocks** - `{#each items as item (item.id)}`
