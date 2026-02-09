---
name: inertia-vue-development
description: "Develops Inertia.js v1 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using Link or router; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Inertia Vue Development

## When to Apply

Activate this skill when:

- Creating or modifying Vue page components for Inertia
- Working with forms in Vue (using `router.post`)
- Implementing client-side navigation with `<Link>` or `router`
- Building Vue-specific features with the Inertia protocol

## Documentation

Use `search-docs` for detailed Inertia v1 Vue patterns and documentation.

## Basic Usage

### Page Components Location

Vue page components should be placed in the `{{ $assist->inertia()->pagesDirectory() }}` directory.

### Page Component Structure

Important: Vue components must have a single root element.

@verbatim
@boostsnippet("Basic Vue Page Component", "vue")
<script setup>
defineProps({
    users: Array
})
</script>

<template>
    <div>
        <h1>Users</h1>
        <ul>
            <li v-for="user in users" :key="user.id">
                {{ user.name }}
            </li>
        </ul>
    </div>
</template>
@endboostsnippet
@endverbatim

## Client-Side Navigation

### Basic Link Component

Use `<Link>` for client-side navigation instead of traditional `<a>` tags:

@boostsnippet("Inertia Vue Navigation", "vue")
<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <div>
        <Link href="/">Home</Link>
        <Link href="/users">Users</Link>
        <Link :href="`/users/${user.id}`">View User</Link>
    </div>
</template>
@endboostsnippet

### Link With Method

@boostsnippet("Link With POST Method", "vue")
<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <Link href="/logout" method="post" as="button">
        Logout
    </Link>
</template>
@endboostsnippet

### Programmatic Navigation

@boostsnippet("Router Visit", "vue")
<script setup>
import { router } from '@inertiajs/vue3'

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

@boostsnippet("Form with router.post", "vue")
<script setup>
import { router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'

const form = reactive({
    name: '',
    email: '',
})

const processing = ref(false)

function handleSubmit() {
    processing.value = true

    router.post('/users', form, {
        onFinish: () => processing.value = false,
    })
}
</script>

<template>
    <form @submit.prevent="handleSubmit">
        <input type="text" v-model="form.name" />
        <input type="email" v-model="form.email" />
        <button type="submit" :disabled="processing">
            Create User
        </button>
    </form>
</template>
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
- Using multiple root elements in Vue components (while Vue 3 supports this, a single root is recommended for Inertia v1 compatibility)
- Trying to use Inertia v2 features (deferred props, `<Form>` component, etc.) in v1 projects
- Using `<form>` without preventing default submission (use `@submit.prevent`)
- Not handling loading states during form submission
