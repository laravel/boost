## Inertia + Svelte

- Svelte page components should be placed in `resources/js/Pages`.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.
- Use `<Form>` component (v2.1.0+) or `useForm` for forms.
- Use/activate `inertia-svelte-development` when working with Svelte pages, forms, or navigation.

@boostsnippet("Inertia Client Navigation", "svelte")
import { inertia, Link } from '@inertiajs/svelte'

<Link href="/">Home</Link>
@endboostsnippet

