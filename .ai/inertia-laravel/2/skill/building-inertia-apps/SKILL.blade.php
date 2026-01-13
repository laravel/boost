---
name: building-inertia-apps
description: >-
  Build single-page applications with Inertia.js v2 and Laravel.
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
- Using deferred props, prefetching, or polling
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

## Inertia v2 Features

Make use of all Inertia features from v1 and v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### New in v2
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll
- Polling
- Prefetching

### Deferred Props & Empty States
When using deferred props on the frontend, you should add a nice empty state with pulsing/animated skeleton.

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

@if($assist->inertia()->hasFormComponent())
The recommended way to build forms when using Inertia is with the `<Form>` component. Use the `search-docs` tool with a query of `form component` for guidance.

Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use the `search-docs` tool with a query of `useForm helper` for guidance.

@if($assist->inertia()->hasFormComponentResets())
`resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use the `search-docs` tool with a query of `form component resetting` for guidance.
@else
This version of Inertia does not support `resetOnError`, `resetOnSuccess`, or `setDefaultsOnSuccess` on the `<Form>` component. Using these will cause errors.
@endif

@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
### Vue Form Component Example

@verbatim
@boostsnippet("`<Form>` Component Example (Vue)", "vue")
<Form
    action="/users"
    method="post"
    #default="{
        errors,
        hasErrors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        setError,
        clearErrors,
        resetAndClearErrors,
        defaults,
        isDirty,
        reset,
        submit,
  }"
>
    <input type="text" name="name" />

    <div v-if="errors.name">
        {{ errors.name }}
    </div>

    <button type="submit" :disabled="processing">
        {{ processing ? 'Creating...' : 'Create User' }}
    </button>

    <div v-if="wasSuccessful">User created successfully!</div>
</Form>
@endboostsnippet
@endverbatim
@endif

@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_REACT))
### React Form Component Example

@boostsnippet("`<Form>` Component Example (React)", "react")
import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
            errors,
            hasErrors,
            processing,
            wasSuccessful,
            recentlySuccessful,
            clearErrors,
            resetAndClearErrors,
            defaults
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)
@endboostsnippet
@endif
@else
Build forms using the `useForm` helper. Use the code examples and the `search-docs` tool with a query of `useForm helper` for guidance.

@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_VUE))
### Vue useForm Example

@verbatim
@boostsnippet("Inertia Vue useForm example", "vue")
<script setup>
    import { useForm } from '@inertiajs/vue3'

    const form = useForm({
        email: null,
        password: null,
        remember: false,
    })
</script>

<template>
    <form @submit.prevent="form.post('/login')">
        <!-- email -->
        <input type="text" v-model="form.email">
        <div v-if="form.errors.email">{{ form.errors.email }}</div>
        <!-- password -->
        <input type="password" v-model="form.password">
        <div v-if="form.errors.password">{{ form.errors.password }}</div>
        <!-- remember me -->
        <input type="checkbox" v-model="form.remember"> Remember Me
        <!-- submit -->
        <button type="submit" :disabled="form.processing">Login</button>
    </form>
</template>
@endboostsnippet
@endverbatim
@endif

@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::INERTIA_REACT))
### React useForm Example

@boostsnippet("Inertia React useForm Example", "react")
import { useForm } from '@inertiajs/react'

const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit(e) {
    e.preventDefault()
    post('/login')
}

return (
<form onSubmit={submit}>
    <input type="text" value={data.email} onChange={e => setData('email', e.target.value)} />
    {errors.email && <div>{errors.email}</div>}
    <input type="password" value={data.password} onChange={e => setData('password', e.target.value)} />
    {errors.password && <div>{errors.password}</div>}
    <input type="checkbox" checked={data.remember} onChange={e => setData('remember', e.target.checked)} /> Remember Me
    <button type="submit" disabled={processing}>Login</button>
</form>
)
@endboostsnippet
@endif
@endif

## Common Pitfalls

- Using traditional `<a>` links instead of Inertia's `<Link>` component (breaks SPA behavior)
- Forgetting to add loading states with deferred props
- Not checking Inertia version before using newer features like `<Form>` component
- Placing page components outside `resources/js/Pages` without updating bundler config
