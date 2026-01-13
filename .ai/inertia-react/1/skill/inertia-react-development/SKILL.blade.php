---
name: inertia-react-development
description: >-
  Develop Inertia.js v1 React client-side applications. MUST activate when creating
  React pages, forms, or navigation; using Link or router; or when user mentions
  React with Inertia, React pages, React forms, or React navigation.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Inertia React Development

## When to Use This Skill

Activate this skill when:
- Creating or modifying React page components for Inertia
- Working with forms in React (using `router.post` or `useForm` if available)
- Implementing client-side navigation with `<Link>` or `router`
- Building React-specific features with the Inertia protocol

## Core Patterns

### Page Components Location

React page components should be placed in the `resources/js/Pages` directory.

### Page Component Structure

@boostsnippet("Basic React Page Component", "react")
export default function UsersIndex({ users }) {
    return (
        <div>
            <h1>Users</h1>
            <ul>
                {users.map(user => (
                    <li key={user.id}>{user.name}</li>
                ))}
            </ul>
        </div>
    )
}
@endboostsnippet

### Documentation

Use the `search-docs` tool for accurate guidance on all things Inertia.

## Client-Side Navigation

### Basic Link Component

Use `<Link>` for client-side navigation instead of traditional `<a>` tags:

@boostsnippet("Inertia React Navigation", "react")
import { Link } from '@inertiajs/react'

<Link href="/">Home</Link>
<Link href="/users">Users</Link>
<Link href={`/users/${user.id}`}>View User</Link>
@endboostsnippet

### Link with Method

@boostsnippet("Link with POST Method", "react")
import { Link } from '@inertiajs/react'

<Link href="/logout" method="post" as="button">
    Logout
</Link>
@endboostsnippet

### Programmatic Navigation

@boostsnippet("Router Visit", "react")
import { router } from '@inertiajs/react'

function handleClick() {
    router.visit('/users')
}

// Or with options
router.visit('/users', {
    method: 'post',
    data: { name: 'John' },
    onSuccess: () => console.log('Success!'),
})
@endboostsnippet

## Form Handling

### Using router.post

@boostsnippet("Form with router.post", "react")
import { router } from '@inertiajs/react'
import { useState } from 'react'

export default function CreateUser() {
    const [values, setValues] = useState({
        name: '',
        email: '',
    })
    const [processing, setProcessing] = useState(false)

    function handleSubmit(e) {
        e.preventDefault()
        setProcessing(true)

        router.post('/users', values, {
            onFinish: () => setProcessing(false),
        })
    }

    return (
        <form onSubmit={handleSubmit}>
            <input
                type="text"
                value={values.name}
                onChange={e => setValues({ ...values, name: e.target.value })}
            />
            <input
                type="email"
                value={values.email}
                onChange={e => setValues({ ...values, email: e.target.value })}
            />
            <button type="submit" disabled={processing}>
                Create User
            </button>
        </form>
    )
}
@endboostsnippet

### Using useForm Hook (if available)

Check the Inertia documentation to confirm if `useForm` is available in your version:

@boostsnippet("useForm Hook Example", "react")
import { useForm } from '@inertiajs/react'

export default function CreateUser() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
    })

    function submit(e) {
        e.preventDefault()
        post('/users')
    }

    return (
        <form onSubmit={submit}>
            <input
                type="text"
                value={data.name}
                onChange={e => setData('name', e.target.value)}
            />
            {errors.name && <div>{errors.name}</div>}

            <input
                type="email"
                value={data.email}
                onChange={e => setData('email', e.target.value)}
            />
            {errors.email && <div>{errors.email}</div>}

            <button type="submit" disabled={processing}>
                Create User
            </button>
        </form>
    )
}
@endboostsnippet

## Inertia v1 Limitations

Inertia v1 does **not** support these v2 features:
- `<Form>` component
- Deferred props
- Prefetching
- Polling
- Infinite scrolling with `WhenVisible`
- Merging props

Do not use these features in v1 projects.

## Server-Side Patterns

Server-side patterns (Inertia::render, props, middleware) are covered in inertia-laravel guidelines.

## Common Pitfalls

- Using traditional `<a>` links instead of Inertia's `<Link>` component (breaks SPA behavior)
- Trying to use Inertia v2 features (deferred props, `<Form>` component, etc.) in v1 projects
- Using `<form>` without preventing default submission (use `e.preventDefault()`)
- Not handling loading states during form submission
