---
name: building-inertia-apps
description: >-
  Build single-page applications with Inertia.js v1 and Laravel.
  Use when working with Inertia pages, forms, navigation, or client-side state.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Building Inertia Apps

## When to Use This Skill

Activate this skill when:
- Creating or modifying Inertia page components
- Working with forms and form validation
- Implementing client-side navigation
- Building features with the Inertia protocol

## Core Patterns

### Page Components Location

Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (`vite.config.js`).

### Server-Side Rendering

Use `Inertia::render()` for server-side routing instead of traditional Blade views:

<code-snippet name="Inertia Render Example" lang="php">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>

### Documentation

Use the `search-docs` tool for accurate guidance on all things Inertia.

## Inertia v1 Limitations

Inertia v1 does not come with these features. Do not recommend using these Inertia v2 features directly:
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll
- Polling
- Prefetching

## Client-Side Navigation

@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
### Vue Navigation

Vue components must have a single root element. Use `router.visit()` or `<Link>` for navigation instead of traditional links:

@boostsnippet("Inertia Vue Navigation", "vue")
import { Link } from '@inertiajs/vue3'
<Link href="/">Home</Link>
@endboostsnippet
@endif

@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_REACT))
### React Navigation

Use `router.visit()` or `<Link>` for navigation instead of traditional links:

@boostsnippet("Inertia React Navigation", "react")
import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>
@endboostsnippet
@endif

## Form Handling

For form handling in Inertia pages, use `router.post` and related methods. Do not use regular forms.

@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
### Vue Form Example

@verbatim
<code-snippet name="Inertia Vue Form Example" lang="vue">
<script setup>
    import { reactive } from 'vue'
    import { router } from '@inertiajs/vue3'
    import { usePage } from '@inertiajs/vue3'

    const page = usePage()

    const form = reactive({
        first_name: null,
        last_name: null,
        email: null,
    })

    function submit() {
        router.post('/users', form)
    }
</script>

<template>
    <h1>Create {{ page.modelName }}</h1>
    <form @submit.prevent="submit">
        <label for="first_name">First name:</label>
        <input id="first_name" v-model="form.first_name" />
        <label for="last_name">Last name:</label>
        <input id="last_name" v-model="form.last_name" />
        <label for="email">Email:</label>
        <input id="email" v-model="form.email" />
        <button type="submit">Submit</button>
    </form>
</template>
</code-snippet>
@endverbatim
@endif

## Common Pitfalls

- Using traditional `<a>` links instead of Inertia's `<Link>` component (breaks SPA behavior)
- Trying to use Inertia v2 features (deferred props, polling, etc.) in v1 projects
- Placing page components outside `resources/js/Pages` without updating bundler config
