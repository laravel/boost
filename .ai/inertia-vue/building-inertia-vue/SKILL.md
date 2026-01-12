---
name: building-inertia-vue
description: Building Inertia.js applications with Vue.js. Use when creating Vue components, managing state, handling forms, or integrating with Laravel backend.
---

# Building Inertia Vue Apps

## When to use this skill

Use this skill when the user asks about:
- Creating Vue components for Inertia apps
- Vue 3 Composition API with Inertia
- Form handling with Vue and Inertia
- Reactive state management
- TypeScript with Inertia Vue

## Page Components

Basic page component with Composition API:

```vue
<!-- resources/js/Pages/Users/Index.vue -->
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Layouts/AppLayout.vue'

defineProps({
    users: Object,
    filters: Object,
})
</script>

<template>
    <Layout>
        <Head title="Users" />

        <h1>Users</h1>

        <Link :href="route('users.create')" class="btn">
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
                <tr v-for="user in users.data" :key="user.id">
                    <td>{{ user.name }}</td>
                    <td>{{ user.email }}</td>
                    <td>
                        <Link :href="route('users.edit', user.id)">
                            Edit
                        </Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </Layout>
</template>
```

## Layouts

Persistent layouts:

```vue
<!-- resources/js/Layouts/AppLayout.vue -->
<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const auth = computed(() => page.props.auth)
const flash = computed(() => page.props.flash)
</script>

<template>
    <div class="min-h-screen">
        <nav>
            <Link href="/">Home</Link>
            <Link href="/users">Users</Link>
            <template v-if="auth.user">
                <Link href="/logout" method="post" as="button">
                    Logout
                </Link>
            </template>
            <template v-else>
                <Link href="/login">Login</Link>
            </template>
        </nav>

        <div v-if="flash.success" class="alert alert-success">
            {{ flash.success }}
        </div>

        <main>
            <slot />
        </main>
    </div>
</template>
```

Define layout on page:
```vue
<script setup>
import Layout from '@/Layouts/AppLayout.vue'

defineOptions({
    layout: Layout,
})
</script>
```

## Forms with useForm

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    email: '',
    role: 'user',
})

function submit() {
    form.post(route('users.store'), {
        onSuccess: () => form.reset(),
        preserveScroll: true,
    })
}
</script>

<template>
    <form @submit.prevent="submit">
        <div>
            <label>Name</label>
            <input
                v-model="form.name"
                type="text"
                :class="{ error: form.errors.name }"
            />
            <span v-if="form.errors.name" class="error">
                {{ form.errors.name }}
            </span>
        </div>

        <div>
            <label>Email</label>
            <input v-model="form.email" type="email" />
            <span v-if="form.errors.email" class="error">
                {{ form.errors.email }}
            </span>
        </div>

        <div>
            <label>Role</label>
            <select v-model="form.role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Create User' }}
        </button>
    </form>
</template>
```

## Edit Forms

Pre-populate with existing data:

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    user: Object,
})

const form = useForm({
    name: props.user.name,
    email: props.user.email,
})

function submit() {
    form.put(route('users.update', props.user.id))
}
</script>

<template>
    <form @submit.prevent="submit">
        <!-- Form fields -->
    </form>
</template>
```

## Delete with Confirmation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    user: Object,
})

const deleteForm = useForm({})

function destroy() {
    if (confirm('Are you sure?')) {
        deleteForm.delete(route('users.destroy', props.user.id))
    }
}
</script>

<template>
    <button @click="destroy" :disabled="deleteForm.processing">
        Delete
    </button>
</template>
```

## usePage Composable

Access shared data:

```vue
<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const flash = computed(() => page.props.flash)
</script>

<template>
    <div>
        <span v-if="user">Welcome, {{ user.name }}</span>
        <Link v-else href="/login">Login</Link>
    </div>
</template>
```

## router for Programmatic Navigation

```vue
<script setup>
import { router } from '@inertiajs/vue3'

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

function reload() {
    router.reload({ only: ['users'] })
}
</script>
```

## File Uploads

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    avatar: null,
})

function submit() {
    form.post(route('profile.update'), {
        forceFormData: true,
    })
}
</script>

<template>
    <form @submit.prevent="submit">
        <input v-model="form.name" type="text" />

        <input
            type="file"
            @input="form.avatar = $event.target.files[0]"
        />

        <progress
            v-if="form.progress"
            :value="form.progress.percentage"
            max="100"
        >
            {{ form.progress.percentage }}%
        </progress>

        <button type="submit" :disabled="form.processing">
            Upload
        </button>
    </form>
</template>
```

## Search with Debounce

```vue
<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'

const props = defineProps({
    users: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')

const debouncedSearch = debounce((value) => {
    router.get(route('users.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
    })
}, 300)

watch(search, (value) => {
    debouncedSearch(value)
})
</script>

<template>
    <input
        v-model="search"
        type="text"
        placeholder="Search users..."
    />
</template>
```

## TypeScript

Type your components:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

interface User {
    id: number
    name: string
    email: string
}

interface Props {
    users: {
        data: User[]
        links: any
    }
    filters: {
        search?: string
    }
}

const props = defineProps<Props>()

const form = useForm({
    name: '',
    email: '',
})
</script>
```

## Head Component

Manage document head:

```vue
<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
    user: Object,
})
</script>

<template>
    <Head>
        <title>{{ user.name }} - Users</title>
        <meta name="description" :content="`Profile of ${user.name}`" />
    </Head>

    <!-- Page content -->
</template>
```

## Best Practices

1. **Use Composition API** - Better TypeScript support
2. **Persistent layouts** - Prevent remounts with `defineOptions`
3. **Leverage useForm** - Built-in form state and validation
4. **Type your props** - Use TypeScript interfaces
5. **Computed properties** - For derived page props
6. **Debounce inputs** - Reduce server requests
7. **Partial reloads** - Use `only` option for efficiency
