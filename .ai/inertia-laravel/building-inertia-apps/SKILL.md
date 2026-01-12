---
name: building-inertia-apps
description: Building modern single-page applications with Inertia.js and Laravel. Use when creating SPAs, handling page navigation, sharing data, or managing forms.
---

# Building Inertia Apps

## When to use this skill

Use this skill when the user asks about:
- Building SPAs with Laravel and Inertia
- Page components and navigation
- Sharing data between server and client
- Form handling with Inertia
- Progress indicators and partial reloads

## Basic Setup

Inertia connects Laravel backend with frontend frameworks (React, Vue, Svelte):

```php
// routes/web.php
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all(),
    ]);
});
```

## Controllers

Standard Laravel controllers work with Inertia:

```php
class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Users/Index', [
            'users' => User::query()
                ->when(request('search'), fn ($q, $search) =>
                    $q->where('name', 'like', "%{$search}%")
                )
                ->paginate(10)
                ->withQueryString(),
            'filters' => request()->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
        ]);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted.');
    }
}
```

## Shared Data

Share data globally via middleware:

```php
// app/Http/Middleware/HandleInertiaRequests.php
class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
```

## Lazy Loading Props

Defer expensive data loading:

```php
return Inertia::render('Users/Index', [
    // Always included
    'users' => User::paginate(10),

    // Only loaded when needed (on first visit or explicit request)
    'stats' => Inertia::lazy(fn () => [
        'total' => User::count(),
        'active' => User::where('active', true)->count(),
    ]),

    // Only included on first visit
    'categories' => Inertia::always(fn () => Category::all()),
]);
```

## Links and Navigation

Use Inertia's Link component instead of anchor tags:

```jsx
// React
import { Link } from '@inertiajs/react'

<Link href="/users">Users</Link>
<Link href={route('users.edit', user.id)}>Edit</Link>
<Link href="/users" method="post" data={{ name: 'John' }}>Create</Link>
<Link href="/logout" method="post" as="button">Logout</Link>

// Preserve scroll position
<Link href="/users" preserveScroll>Users</Link>

// Preserve state (for tabs, etc.)
<Link href="/users?tab=active" preserveState>Active</Link>
```

```js
// Programmatic navigation
import { router } from '@inertiajs/react'

router.visit('/users')
router.get('/users', { search: 'john' })
router.post('/users', { name: 'John' })
router.delete(`/users/${user.id}`)

// With options
router.visit('/users', {
    method: 'get',
    data: { search: 'john' },
    preserveState: true,
    preserveScroll: true,
    only: ['users'], // Partial reload
})
```

## Forms

Inertia provides form helpers:

```jsx
// React
import { useForm } from '@inertiajs/react'

export default function Create() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
    })

    function submit(e) {
        e.preventDefault()
        post('/users', {
            onSuccess: () => reset(),
        })
    }

    return (
        <form onSubmit={submit}>
            <input
                value={data.name}
                onChange={e => setData('name', e.target.value)}
            />
            {errors.name && <span>{errors.name}</span>}

            <input
                value={data.email}
                onChange={e => setData('email', e.target.value)}
            />
            {errors.email && <span>{errors.email}</span>}

            <button type="submit" disabled={processing}>
                Create
            </button>
        </form>
    )
}
```

Form methods:
- `get(url)`, `post(url)`, `put(url)`, `patch(url)`, `delete(url)`
- `transform(callback)` - Transform data before submission
- `reset()`, `reset('field')` - Reset form data
- `clearErrors()`, `clearErrors('field')` - Clear validation errors

## File Uploads

```jsx
const { data, setData, post, progress } = useForm({
    name: '',
    avatar: null,
})

function submit(e) {
    e.preventDefault()
    post('/users', {
        forceFormData: true, // Required for file uploads
    })
}

<input
    type="file"
    onChange={e => setData('avatar', e.target.files[0])}
/>

{progress && <progress value={progress.percentage} max="100" />}
```

## Progress Indicators

Show loading state during navigation:

```jsx
// React
import { router } from '@inertiajs/react'
import { useState, useEffect } from 'react'

function useLoading() {
    const [loading, setLoading] = useState(false)

    useEffect(() => {
        router.on('start', () => setLoading(true))
        router.on('finish', () => setLoading(false))
    }, [])

    return loading
}
```

Or use NProgress:
```js
// app.js
import NProgress from 'nprogress'
import { router } from '@inertiajs/react'

router.on('start', () => NProgress.start())
router.on('finish', () => NProgress.done())
```

## Partial Reloads

Only reload specific props:

```jsx
router.reload({ only: ['users'] })

// From Link
<Link href="/users" only={['users']}>Refresh Users</Link>
```

## Scroll Management

```jsx
// Preserve scroll position
router.visit('/users', { preserveScroll: true })

// Reset scroll for specific elements
router.visit('/users', {
    preserveScroll: (page) => page.url.includes('page='),
})
```

## Server-Side Rendering (SSR)

Enable SSR in `config/inertia.php`:

```php
'ssr' => [
    'enabled' => true,
    'url' => 'http://127.0.0.1:13714',
],
```

Run SSR server:
```bash
php artisan inertia:start-ssr
```

## Best Practices

1. **Use route helpers** - `route('users.index')` for named routes
2. **Paginate large datasets** - Don't send all records
3. **Use lazy props** - Defer expensive computations
4. **Handle loading states** - Show progress indicators
5. **Validate on server** - Never trust client-side validation
6. **Use partial reloads** - Only fetch what changed
7. **Share common data** - Use HandleInertiaRequests middleware
