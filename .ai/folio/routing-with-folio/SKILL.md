---
name: routing-with-folio
description: File-based routing with Laravel Folio. Use when creating page-based routes, handling route parameters, or setting up middleware for pages.
---

# Routing with Folio

## When to use this skill

Use this skill when the user asks about:
- File-based routing in Laravel
- Creating pages without route definitions
- Route parameters in Folio
- Middleware for Folio pages

## Basic Pages

Create pages in `resources/views/pages`:

```
resources/views/pages/
├── index.blade.php          → /
├── about.blade.php          → /about
├── contact.blade.php        → /contact
└── users/
    ├── index.blade.php      → /users
    └── create.blade.php     → /users/create
```

## Installation

```bash
composer require laravel/folio
php artisan folio:install
```

Register in `AppServiceProvider`:

```php
use Laravel\Folio\Folio;

public function boot(): void
{
    Folio::path(resource_path('views/pages'));
}
```

## Route Parameters

Use brackets for dynamic segments:

```
resources/views/pages/
└── users/
    └── [id].blade.php       → /users/{id}
```

Access in template:
```blade
{{-- resources/views/pages/users/[id].blade.php --}}
<h1>User {{ $id }}</h1>
```

### Model Binding

```
resources/views/pages/
└── users/
    └── [User].blade.php     → /users/{user}
```

```blade
{{-- resources/views/pages/users/[User].blade.php --}}
<h1>{{ $user->name }}</h1>
<p>{{ $user->email }}</p>
```

### Custom Column Binding

```
resources/views/pages/
└── users/
    └── [User:username].blade.php  → /users/{user:username}
```

### Soft Deleted Models

```php
<?php
use function Laravel\Folio\{withTrashed};

withTrashed();
?>

<h1>{{ $user->name }}</h1>
```

## Multiple Parameters

```
resources/views/pages/
└── users/
    └── [User]/
        └── posts/
            └── [Post].blade.php  → /users/{user}/posts/{post}
```

```blade
<h1>{{ $post->title }}</h1>
<p>By {{ $user->name }}</p>
```

## Catch-All Routes

Use `[...segments]` for catch-all:

```
resources/views/pages/
└── docs/
    └── [...slug].blade.php   → /docs/*
```

```blade
{{-- $slug is an array --}}
@foreach($slug as $segment)
    <span>{{ $segment }}</span>
@endforeach
```

## Page Directives

### Middleware

```php
<?php
use function Laravel\Folio\{middleware};

middleware(['auth', 'verified']);
?>

<h1>Dashboard</h1>
```

### Named Routes

```php
<?php
use function Laravel\Folio\{name};

name('user.profile');
?>
```

Use in links:
```blade
<a href="{{ route('user.profile', $user) }}">Profile</a>
```

### Custom Render Logic

```php
<?php
use function Laravel\Folio\{render};
use App\Models\User;
use Illuminate\View\View;

render(function (View $view, User $user) {
    if ($user->isPrivate()) {
        abort(403);
    }

    return $view->with('posts', $user->posts);
});
?>

<h1>{{ $user->name }}</h1>
@foreach($posts as $post)
    <div>{{ $post->title }}</div>
@endforeach
```

## With Volt

Combine Folio with Volt for reactive pages:

```php
<?php
// resources/views/pages/counter.blade.php
use function Livewire\Volt\{state};

state(['count' => 0]);

$increment = fn () => $this->count++;
?>

<div>
    <h1>Count: {{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>
```

## Index Pages

`index.blade.php` serves the directory route:

```
resources/views/pages/
├── index.blade.php           → /
└── users/
    ├── index.blade.php       → /users
    └── [User].blade.php      → /users/{user}
```

## Multiple Page Directories

```php
// AppServiceProvider
Folio::path(resource_path('views/pages'))
    ->middleware(['web']);

Folio::path(resource_path('views/admin'))
    ->uri('/admin')
    ->middleware(['web', 'auth', 'admin']);
```

## Route Priority

More specific routes take precedence:

```
resources/views/pages/users/
├── index.blade.php      → /users (1st priority)
├── create.blade.php     → /users/create (2nd priority)
└── [User].blade.php     → /users/{user} (3rd priority)
```

## Artisan Commands

```bash
# List all Folio routes
php artisan folio:list

# Create a new page
php artisan make:folio users/index
php artisan make:folio users/[User]
```

## Layout Integration

```blade
{{-- resources/views/pages/dashboard.blade.php --}}
<x-app-layout>
    <h1>Dashboard</h1>
    <p>Welcome back!</p>
</x-app-layout>
```

Or with sections:
```blade
@extends('layouts.app')

@section('content')
    <h1>Dashboard</h1>
@endsection
```

## Best Practices

1. **Use model binding** - `[User].blade.php` over `[id].blade.php`
2. **Group related pages** - Use directories for organization
3. **Apply middleware early** - Define at top of page
4. **Name important routes** - For easy linking
5. **Combine with Volt** - For reactive pages
6. **Keep pages simple** - Extract complex logic to services
