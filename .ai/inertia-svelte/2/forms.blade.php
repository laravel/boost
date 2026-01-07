@php
    /** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Inertia v2 + Svelte Forms

- There are critical differences between Svelte 4 and 5; use the `search-docs` tool for up-to-date guidance.

@if($assist->inertia()->hasFormComponent())
@boostsnippet("`<Form>` Component Example", "svelte5")
<Form action="/users" method="post">
    {#snippet children({
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
    })}
    <input type="text" name="name" />

    {#if errors.name}
    <div>{errors.name}</div>
    {/if}

    <button type="submit" disabled={processing}>
        {processing ? 'Creating...' : 'Create User'}
    </button>

    {#if wasSuccessful}
    <div>User created successfully!</div>
    {/if}
    {/snippet}
</Form>
@endboostsnippet
@endif

@if($assist->inertia()->hasFormComponent() === false)
{{-- Inertia 2.0.x, not 2.1.0 or higher. So they still need to use 'useForm' --}}
@boostsnippet("Inertia Svelte useForm Example", "svelte")
<script>
    import { useForm } from '@inertiajs/svelte'

    const form = useForm({
        email: null,
        password: null,
        remember: false,
    })

    function submit(e) {
        e.preventDefault() /* Only required with Svelte 5 */
        $form.post('/login')
    }
</script>

<form onsubmit={submit}>
    <input type="text" bind:value={$form.email} />
    {#if $form.errors.email}
    <div class="form-error">{$form.errors.email}</div>
    {/if}
    <input type="password" bind:value={$form.password} />
    {#if $form.errors.password}
    <div class="form-error">{$form.errors.password}</div>
    {/if}
    <input type="checkbox" bind:checked={$form.remember} /> Remember Me
    <button type="submit" disabled={$form.processing}>Submit</button>
</form>
@endboostsnippet
@endif
