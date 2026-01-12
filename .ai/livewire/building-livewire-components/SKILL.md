---
name: building-livewire-components
description: Building reactive Laravel Blade components with Livewire. Use when creating dynamic UI, forms with real-time validation, or interactive elements without JavaScript.
---

# Building Livewire Components

## When to use this skill

Use this skill when the user asks about:
- Creating Livewire components
- Real-time form validation
- Dynamic UI without writing JavaScript
- Server-side reactive components
- Wire attributes (wire:model, wire:click, etc.)

## Creating Components

Create a component:

```bash
php artisan make:livewire CreatePost
```

This creates:
- `app/Livewire/CreatePost.php` - Component class
- `resources/views/livewire/create-post.blade.php` - View

For nested components:

```bash
php artisan make:livewire Posts/CreatePost
```

## Properties and Data Binding

Public properties are automatically available in the view and can be bound to inputs:

```php
class CreatePost extends Component
{
    public string $title = '';
    public string $content = '';

    public function render()
    {
        return view('livewire.create-post');
    }
}
```

In the view:

```blade
<input wire:model="title" type="text">
<textarea wire:model="content"></textarea>
```

### Wire Model Modifiers

- `wire:model` - Updates on input event (default)
- `wire:model.live` - Updates on every keystroke
- `wire:model.blur` - Updates when input loses focus
- `wire:model.change` - Updates on change event
- `wire:model.debounce.500ms` - Debounce updates

## Actions

Public methods can be called from the view:

```php
class CreatePost extends Component
{
    public string $title = '';
    public string $content = '';

    public function save()
    {
        $this->validate([
            'title' => 'required|min:3',
            'content' => 'required',
        ]);

        Post::create([
            'title' => $this->title,
            'content' => $this->content,
        ]);

        return redirect()->route('posts.index');
    }
}
```

Trigger actions from the view:

```blade
<form wire:submit="save">
    <input wire:model="title" type="text">
    @error('title') <span>{{ $message }}</span> @enderror

    <textarea wire:model="content"></textarea>
    @error('content') <span>{{ $message }}</span> @enderror

    <button type="submit">Save</button>
</form>
```

## Lifecycle Hooks

```php
class UserProfile extends Component
{
    public User $user;
    public string $search = '';

    // Called when component is first instantiated
    public function mount(User $user)
    {
        $this->user = $user;
    }

    // Called when a specific property is updated
    public function updatedSearch()
    {
        $this->resetPage();
    }

    // Called before a property is updated
    public function updatingSearch($value)
    {
        // $value is the new value
    }
}
```

## Loading States

Show loading indicators during server requests:

```blade
<button wire:click="save">
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</button>

<!-- Target specific actions -->
<span wire:loading wire:target="save">Saving...</span>

<!-- Add CSS classes while loading -->
<button wire:loading.class="opacity-50" wire:click="save">Save</button>

<!-- Disable while loading -->
<button wire:loading.attr="disabled" wire:click="save">Save</button>
```

## Events

Dispatch events from components:

```php
// Dispatch an event
$this->dispatch('post-created', postId: $post->id);

// Listen for events
#[On('post-created')]
public function handlePostCreated(int $postId)
{
    // Handle the event
}
```

In the view:

```blade
<!-- Listen for browser events -->
<div wire:click="$dispatch('post-selected', { id: 1 })">
    Select Post
</div>
```

## Form Objects

For complex forms, use Form Objects:

```php
use Livewire\Form;

class PostForm extends Form
{
    public string $title = '';
    public string $content = '';

    protected function rules()
    {
        return [
            'title' => 'required|min:3',
            'content' => 'required',
        ];
    }
}
```

Use in component:

```php
class CreatePost extends Component
{
    public PostForm $form;

    public function save()
    {
        $this->form->validate();

        Post::create($this->form->all());

        $this->form->reset();
    }
}
```

## File Uploads

```php
use Livewire\WithFileUploads;

class UploadPhoto extends Component
{
    use WithFileUploads;

    public $photo;

    public function save()
    {
        $this->validate([
            'photo' => 'image|max:1024',
        ]);

        $this->photo->store('photos');
    }
}
```

```blade
<input type="file" wire:model="photo">

@error('photo') <span>{{ $message }}</span> @enderror

<div wire:loading wire:target="photo">Uploading...</div>

@if ($photo)
    <img src="{{ $photo->temporaryUrl() }}">
@endif
```

## Pagination

```php
use Livewire\WithPagination;

class PostList extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.post-list', [
            'posts' => Post::paginate(10),
        ]);
    }
}
```

## Best Practices

1. **Components require a single root element**
2. **Use `wire:key` in loops**:

```blade
@foreach ($items as $item)
    <div wire:key="item-{{ $item->id }}">
        {{ $item->name }}
    </div>
@endforeach
```

3. **Always validate and authorize** - Livewire actions are like HTTP requests
4. **Use `wire:loading` and `wire:dirty`** for better UX
5. **Prefer lifecycle hooks** (`mount()`, `updatedFoo()`) for initialization and reactive side effects

## Testing

See [references/testing.md](references/testing.md) for testing patterns.
