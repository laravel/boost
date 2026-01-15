# Inertia v2 Features (Svelte)

## Deferred Props

Load data after initial page render. Handle the `undefined` state:

```svelte
<script>
export let users
</script>

<div>
    <h1>Users</h1>
    {#if !users}
        <div class="animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
        </div>
    {:else}
        <ul>
            {#each users as user (user.id)}
                <li>{user.name}</li>
            {/each}
        </ul>
    {/if}
</div>
```

## Polling

Automatically refresh data at intervals:

```svelte
<script>
import { router } from '@inertiajs/svelte'
import { onMount, onDestroy } from 'svelte'

export let stats

let interval

onMount(() => {
    interval = setInterval(() => {
        router.reload({ only: ['stats'] })
    }, 5000)
})

onDestroy(() => clearInterval(interval))
</script>

<div>Active Users: {stats.activeUsers}</div>
```

## Prefetching

Prefetch pages on hover:

```svelte
<Link href="/users" prefetch>Users</Link>
```

**IMPORTANT**: Always use the `search-docs` tool to get the latest documentation and code examples for Inertia.
