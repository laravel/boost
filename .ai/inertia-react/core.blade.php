## Inertia + React

- React page components should be placed in `resources/js/Pages`.
- Use `<Link>` component for navigation instead of traditional `<a>` tags.
- Use `<Form>` component (v2.1.0+) or `useForm` hook for forms.
- Use/activate `inertia-react-development` when working with React pages, forms, or navigation.

@boostsnippet("Inertia React Navigation", "react")
import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>
@endboostsnippet
