---
name: building-inertia-react
description: Building Inertia.js applications with React. Use when creating React components, managing state, handling forms, or integrating with Laravel backend.
---

# Building Inertia React Apps

## When to use this skill

Use this skill when the user asks about:
- Creating React components for Inertia apps
- Managing component state and props
- Form handling with React and Inertia
- React hooks with Inertia
- TypeScript with Inertia React

## Page Components

Basic page component structure:

```jsx
// resources/js/Pages/Users/Index.jsx
import { Head, Link } from '@inertiajs/react'
import Layout from '@/Layouts/AppLayout'

export default function Index({ users, filters }) {
    return (
        <Layout>
            <Head title="Users" />

            <h1>Users</h1>

            <Link href={route('users.create')} className="btn">
                Create User
            </Link>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {users.data.map(user => (
                        <tr key={user.id}>
                            <td>{user.name}</td>
                            <td>{user.email}</td>
                            <td>
                                <Link href={route('users.edit', user.id)}>
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </Layout>
    )
}
```

## Layouts

Persistent layouts that don't remount:

```jsx
// resources/js/Layouts/AppLayout.jsx
import { Link, usePage } from '@inertiajs/react'

export default function AppLayout({ children }) {
    const { auth, flash } = usePage().props

    return (
        <div className="min-h-screen">
            <nav>
                <Link href="/">Home</Link>
                <Link href="/users">Users</Link>
                {auth.user ? (
                    <Link href="/logout" method="post" as="button">
                        Logout
                    </Link>
                ) : (
                    <Link href="/login">Login</Link>
                )}
            </nav>

            {flash.success && (
                <div className="alert alert-success">{flash.success}</div>
            )}

            <main>{children}</main>
        </div>
    )
}

// Set persistent layout on page
Index.layout = page => <AppLayout>{page}</AppLayout>
```

## Forms with useForm

```jsx
import { useForm } from '@inertiajs/react'

export default function Create() {
    const { data, setData, post, processing, errors, reset, isDirty } = useForm({
        name: '',
        email: '',
        role: 'user',
    })

    function handleSubmit(e) {
        e.preventDefault()
        post(route('users.store'), {
            onSuccess: () => reset(),
            onError: (errors) => console.log(errors),
            preserveScroll: true,
        })
    }

    return (
        <form onSubmit={handleSubmit}>
            <div>
                <label>Name</label>
                <input
                    type="text"
                    value={data.name}
                    onChange={e => setData('name', e.target.value)}
                    className={errors.name ? 'error' : ''}
                />
                {errors.name && <span className="error">{errors.name}</span>}
            </div>

            <div>
                <label>Email</label>
                <input
                    type="email"
                    value={data.email}
                    onChange={e => setData('email', e.target.value)}
                />
                {errors.email && <span className="error">{errors.email}</span>}
            </div>

            <div>
                <label>Role</label>
                <select
                    value={data.role}
                    onChange={e => setData('role', e.target.value)}
                >
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" disabled={processing}>
                {processing ? 'Saving...' : 'Create User'}
            </button>
        </form>
    )
}
```

## Edit Forms

Pre-populate with existing data:

```jsx
export default function Edit({ user }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
    })

    function handleSubmit(e) {
        e.preventDefault()
        put(route('users.update', user.id))
    }

    return (
        <form onSubmit={handleSubmit}>
            {/* Form fields */}
        </form>
    )
}
```

## Delete Confirmation

```jsx
import { useForm } from '@inertiajs/react'

function DeleteButton({ user }) {
    const { delete: destroy, processing } = useForm()

    function handleDelete() {
        if (confirm('Are you sure?')) {
            destroy(route('users.destroy', user.id))
        }
    }

    return (
        <button onClick={handleDelete} disabled={processing}>
            Delete
        </button>
    )
}
```

## usePage Hook

Access shared data and props:

```jsx
import { usePage } from '@inertiajs/react'

function UserMenu() {
    const { auth, flash } = usePage().props

    return (
        <div>
            {auth.user ? (
                <span>Welcome, {auth.user.name}</span>
            ) : (
                <Link href="/login">Login</Link>
            )}
        </div>
    )
}
```

## router for Programmatic Navigation

```jsx
import { router } from '@inertiajs/react'

// Simple navigation
router.visit('/users')

// With method and data
router.post('/users', { name: 'John' })

// With options
router.visit('/users', {
    method: 'get',
    data: { search: 'john' },
    preserveState: true,
    preserveScroll: true,
    only: ['users'],
    onSuccess: (page) => console.log('Success!'),
    onError: (errors) => console.log(errors),
    onFinish: () => console.log('Finished'),
})

// Reload current page
router.reload({ only: ['users'] })
```

## File Uploads

```jsx
export default function Upload() {
    const { data, setData, post, progress, processing } = useForm({
        name: '',
        avatar: null,
    })

    function handleSubmit(e) {
        e.preventDefault()
        post(route('profile.update'), {
            forceFormData: true,
        })
    }

    return (
        <form onSubmit={handleSubmit}>
            <input
                type="text"
                value={data.name}
                onChange={e => setData('name', e.target.value)}
            />

            <input
                type="file"
                onChange={e => setData('avatar', e.target.files[0])}
            />

            {progress && (
                <progress value={progress.percentage} max="100">
                    {progress.percentage}%
                </progress>
            )}

            <button type="submit" disabled={processing}>
                Upload
            </button>
        </form>
    )
}
```

## Search and Filters

Debounced search with state preservation:

```jsx
import { useState, useEffect } from 'react'
import { router } from '@inertiajs/react'
import debounce from 'lodash/debounce'

export default function Index({ users, filters }) {
    const [search, setSearch] = useState(filters.search || '')

    const debouncedSearch = debounce((value) => {
        router.get(route('users.index'), { search: value }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)

    function handleSearch(e) {
        const value = e.target.value
        setSearch(value)
        debouncedSearch(value)
    }

    return (
        <div>
            <input
                type="text"
                value={search}
                onChange={handleSearch}
                placeholder="Search users..."
            />

            {/* User list */}
        </div>
    )
}
```

## TypeScript

Type your pages and forms:

```tsx
// types/index.d.ts
interface User {
    id: number
    name: string
    email: string
}

interface PageProps {
    auth: { user: User | null }
    flash: { success?: string; error?: string }
}

// Pages/Users/Index.tsx
import { PageProps } from '@/types'

interface Props extends PageProps {
    users: {
        data: User[]
        links: any
    }
}

export default function Index({ users }: Props) {
    // TypeScript knows users.data is User[]
}
```

## Head Component

Manage document head:

```jsx
import { Head } from '@inertiajs/react'

export default function Show({ user }) {
    return (
        <>
            <Head>
                <title>{user.name} - Users</title>
                <meta name="description" content={`Profile of ${user.name}`} />
            </Head>

            {/* Page content */}
        </>
    )
}
```

## Best Practices

1. **Use persistent layouts** - Prevent unnecessary remounts
2. **Leverage useForm** - Built-in form state management
3. **Type your props** - Use TypeScript for safety
4. **Debounce search inputs** - Reduce server requests
5. **Handle loading states** - Show processing indicators
6. **Use route helpers** - `route('users.index')` for type safety
7. **Partial reloads** - Only fetch changed data with `only`
