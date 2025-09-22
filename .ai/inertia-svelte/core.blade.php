## Inertia + Svelte

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

@boostsnippet("Inertia Client Navigation", "svelte")
import { inertia, Link } from '@inertiajs/svelte'

<a href="/" use:inertia>Home</a>
<Link href="/">Home</Link>
@endboostsnippet
