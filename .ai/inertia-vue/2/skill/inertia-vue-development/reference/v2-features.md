# Inertia v2 Features (Vue)

## Deferred Props

Load data after initial page render. Handle the `undefined` state with skeleton UI:

```vue
<script setup>
defineProps({
    users: Array
})
</script>

<template>
    <div>
        <div v-if="!users" class="animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
        </div>
        <ul v-else>
            <li v-for="user in users" :key="user.id">{{ user.name }}</li>
        </ul>
    </div>
</template>
```

## Polling

Automatically refresh data at intervals:

```vue
<script setup>
import { router } from '@inertiajs/vue3'
import { onMounted, onUnmounted } from 'vue'

defineProps({ stats: Object })

let interval

onMounted(() => {
    interval = setInterval(() => {
        router.reload({ only: ['stats'] })
    }, 5000)
})

onUnmounted(() => clearInterval(interval))
</script>
```

## WhenVisible (Infinite Scroll)

Load more data when user scrolls to element:

```vue
<script setup>
import { WhenVisible } from '@inertiajs/vue3'

defineProps({ users: Object })
</script>

<template>
    <div>
        <div v-for="user in users.data" :key="user.id">{{ user.name }}</div>

        <WhenVisible
            v-if="users.next_page_url"
            data="users"
            :params="{ page: users.current_page + 1 }"
        >
            <template #fallback>Loading more...</template>
        </WhenVisible>
    </div>
</template>
```

## Prefetching

Prefetch pages on hover for faster navigation:

```vue
<Link href="/users" prefetch>Users</Link>
```

**IMPORTANT**: Always use the `search-docs` tool to get the latest documentation and code examples for Inertia.
