# Inertia v2 Features (React)

## Deferred Props

Load data after initial page render. Handle the `undefined` state:

```jsx
export default function UsersIndex({ users }) {
    return (
        <div>
            <h1>Users</h1>
            {!users ? (
                <div className="animate-pulse">
                    <div className="h-4 bg-gray-200 rounded w-3/4 mb-2" />
                </div>
            ) : (
                <ul>
                    {users.map(user => <li key={user.id}>{user.name}</li>)}
                </ul>
            )}
        </div>
    )
}
```

## Polling

Automatically refresh data at intervals:

```jsx
import { router } from '@inertiajs/react'
import { useEffect } from 'react'

export default function Dashboard({ stats }) {
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ['stats'] })
        }, 5000)
        return () => clearInterval(interval)
    }, [])

    return <div>Active Users: {stats.activeUsers}</div>
}
```

## WhenVisible (Infinite Scroll)

Load more data when user scrolls to element:

```jsx
import { WhenVisible } from '@inertiajs/react'

export default function UsersList({ users }) {
    return (
        <div>
            {users.data.map(user => <div key={user.id}>{user.name}</div>)}

            {users.next_page_url && (
                <WhenVisible
                    data="users"
                    params={{ page: users.current_page + 1 }}
                    fallback={<div>Loading more...</div>}
                />
            )}
        </div>
    )
}
```

## Prefetching

Prefetch pages on hover:

```jsx
<Link href="/users" prefetch>Users</Link>
```

Use `search-docs` with "inertia prefetching" for cache strategies.
