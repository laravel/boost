## Inertia v1 + Svelte Forms

- For form handling, use `router.post` and related methods. Do not use regular forms.
@verbatim
<code-snippet name="Inertia Svelte Form Example" lang="svelte">
<script>
    import { router } from '@inertiajs/svelte'

    let values = {
        first_name: null,
        last_name: null,
        email: null,
    }

    function handleSubmit() {
        router.post('/users', values)
    }
</script>

<form on:submit|preventDefault={handleSubmit}>
    <label for="first_name">First name:</label>
    <input id="first_name" bind:value={values.first_name}>

    <label for="last_name">Last name:</label>
    <input id="last_name" bind:value={values.last_name}>

    <label for="email">Email:</label>
    <input id="email" bind:value={values.email}>

    <button type="submit">Submit</button>
</form>
</code-snippet>
@endverbatim
