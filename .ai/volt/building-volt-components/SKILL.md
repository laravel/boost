---
name: building-volt-components
description: Building single-file Livewire components with Volt. Use when creating functional or class-based Volt components with co-located PHP and Blade.
---

# Building Volt Components

## When to use this skill

Use this skill when the user asks about:
- Creating single-file Livewire components
- Functional Volt components
- Class-based Volt components
- State management in Volt

## Functional Components

Simple functional syntax:

```php
<?php
// resources/views/livewire/counter.blade.php

use function Livewire\Volt\{state, computed};

state(['count' => 0]);

$increment = fn () => $this->count++;
$decrement = fn () => $this->count--;

?>

<div>
    <h1>Count: {{ $count }}</h1>
    <button wire:click="increment">+</button>
    <button wire:click="decrement">-</button>
</div>
```

## State Management

```php
<?php

use function Livewire\Volt\{state};

// Simple state
state(['name' => '']);

// State with default from route/prop
state(['user' => fn () => auth()->user()]);

// Multiple state values
state([
    'title' => '',
    'content' => '',
    'published' => false,
]);

?>

<div>
    <input wire:model="name" type="text">
    <p>Hello, {{ $name }}</p>
</div>
```

## Actions

```php
<?php

use function Livewire\Volt\{state};
use App\Models\Post;

state(['title' => '', 'content' => '']);

$save = function () {
    $this->validate([
        'title' => 'required|min:3',
        'content' => 'required',
    ]);

    Post::create([
        'title' => $this->title,
        'content' => $this->content,
    ]);

    $this->redirect('/posts');
};

$reset = fn () => $this->reset(['title', 'content']);

?>

<form wire:submit="save">
    <input wire:model="title" type="text">
    @error('title') <span>{{ $message }}</span> @enderror

    <textarea wire:model="content"></textarea>
    @error('content') <span>{{ $message }}</span> @enderror

    <button type="submit">Save</button>
    <button type="button" wire:click="reset">Clear</button>
</form>
```

## Computed Properties

```php
<?php

use function Livewire\Volt\{state, computed};
use App\Models\Post;

state(['search' => '']);

$posts = computed(function () {
    return Post::query()
        ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
        ->latest()
        ->get();
});

?>

<div>
    <input wire:model.live="search" placeholder="Search...">

    @foreach($this->posts as $post)
        <div>{{ $post->title }}</div>
    @endforeach
</div>
```

## Lifecycle Hooks

```php
<?php

use function Livewire\Volt\{state, mount, updated, on};
use App\Models\User;

state(['user' => null, 'name' => '']);

mount(function (User $user) {
    $this->user = $user;
    $this->name = $user->name;
});

updated(['name' => function ($value) {
    // Called when name is updated
    $this->validateOnly('name');
}]);

// Listen for events
on(['user-updated' => function ($userId) {
    if ($this->user->id === $userId) {
        $this->user->refresh();
    }
}]);

?>

<div>
    <input wire:model="name">
</div>
```

## Class-Based Components

For more complex components:

```php
<?php
// resources/views/livewire/user-profile.blade.php

use Livewire\Volt\Component;
use App\Models\User;

new class extends Component {
    public User $user;
    public string $name = '';
    public string $email = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->dispatch('user-saved');
    }

    public function with(): array
    {
        return [
            'posts' => $this->user->posts()->latest()->get(),
        ];
    }
}

?>

<div>
    <form wire:submit="save">
        <input wire:model="name">
        <input wire:model="email" type="email">
        <button type="submit">Save</button>
    </form>

    <h2>Posts</h2>
    @foreach($posts as $post)
        <div>{{ $post->title }}</div>
    @endforeach
</div>
```

## Forms with Validation

```php
<?php

use function Livewire\Volt\{state, rules};

state([
    'name' => '',
    'email' => '',
    'password' => '',
]);

rules([
    'name' => 'required|min:2',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8',
]);

$register = function () {
    $this->validate();

    User::create([
        'name' => $this->name,
        'email' => $this->email,
        'password' => bcrypt($this->password),
    ]);

    $this->redirect('/dashboard');
};

?>

<form wire:submit="register">
    <div>
        <input wire:model="name" placeholder="Name">
        @error('name') <span>{{ $message }}</span> @enderror
    </div>

    <div>
        <input wire:model="email" type="email" placeholder="Email">
        @error('email') <span>{{ $message }}</span> @enderror
    </div>

    <div>
        <input wire:model="password" type="password" placeholder="Password">
        @error('password') <span>{{ $message }}</span> @enderror
    </div>

    <button type="submit">Register</button>
</form>
```

## File Uploads

```php
<?php

use function Livewire\Volt\{state, usesFileUploads};
use Livewire\WithFileUploads;

usesFileUploads();

state(['photo' => null]);

$save = function () {
    $this->validate(['photo' => 'image|max:1024']);

    $path = $this->photo->store('photos', 'public');

    auth()->user()->update(['photo' => $path]);
};

?>

<form wire:submit="save">
    <input type="file" wire:model="photo">

    @error('photo') <span>{{ $message }}</span> @enderror

    <div wire:loading wire:target="photo">Uploading...</div>

    @if ($photo)
        <img src="{{ $photo->temporaryUrl() }}">
    @endif

    <button type="submit">Save</button>
</form>
```

## Pagination

```php
<?php

use function Livewire\Volt\{computed, usesPagination};
use App\Models\Post;

usesPagination();

$posts = computed(fn () => Post::latest()->paginate(10));

?>

<div>
    @foreach($this->posts as $post)
        <div>{{ $post->title }}</div>
    @endforeach

    {{ $this->posts->links() }}
</div>
```

## Routing

Register Volt pages in routes:

```php
use Livewire\Volt\Volt;

// Single page
Volt::route('/counter', 'counter');

// With parameters
Volt::route('/users/{user}', 'user-profile');

// In route groups
Route::middleware('auth')->group(function () {
    Volt::route('/dashboard', 'dashboard');
    Volt::route('/settings', 'settings');
});
```

## Best Practices

1. **Use functional for simple components** - Less boilerplate
2. **Use class-based for complex logic** - Better organization
3. **Computed properties** - Cache expensive operations
4. **Validate early** - Use real-time validation
5. **Co-locate related code** - Keep PHP and Blade together
6. **Extract shared logic** - Use traits or base components
