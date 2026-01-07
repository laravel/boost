## Inertia v1 + Vue Forms

- For form handling in Inertia pages, use `router.post` and related methods. Do not use regular forms.
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
