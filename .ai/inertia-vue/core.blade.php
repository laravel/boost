## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

@boostsnippet("Inertia Client Navigation", "vue")
    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>
@endboostsnippet
