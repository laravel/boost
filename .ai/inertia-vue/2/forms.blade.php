@php
    /** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Inertia v2 + Vue Forms

@if($assist->inertia()->hasFormComponent())
@boostsnippet("`<Form>` Component Example", "vue")
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
@endif

@if($assist->inertia()->hasFormComponent() === false)
{{-- Inertia 2.0.x, not 2.1.0 or higher. So still need to use 'useForm' --}}
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
@endif
