## Inertia + Vue

**Important**: Vue components must have a single root element.

- Vue page components should be placed in `resources/js/Pages`.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.
- Use `<Form>` component (v2.1.0+) or `useForm` composable for forms.
- Use/activate `inertia-vue-development` when working with Vue pages, forms, or navigation.

@boostsnippet("Inertia Vue Navigation", "vue")
    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>
@endboostsnippet
