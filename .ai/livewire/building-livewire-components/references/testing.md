# Testing Livewire Components

## Basic Component Test

```php
use Livewire\Livewire;

test('can render component', function () {
    Livewire::test(Counter::class)
        ->assertStatus(200)
        ->assertSee('Count: 0');
});
```

## Testing Properties

```php
test('can set and assert properties', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'My Post')
        ->set('content', 'Post content')
        ->assertSet('title', 'My Post')
        ->assertSet('content', 'Post content');
});
```

## Testing Actions

```php
test('can call actions', function () {
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->call('increment')
        ->assertSet('count', 2);
});
```

## Testing Validation

```php
test('validates required fields', function () {
    Livewire::test(CreatePost::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

test('passes validation with valid data', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Valid Title')
        ->set('content', 'Valid content')
        ->call('save')
        ->assertHasNoErrors();
});
```

## Testing Redirects

```php
test('redirects after save', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'New Post')
        ->set('content', 'Content here')
        ->call('save')
        ->assertRedirect(route('posts.index'));
});
```

## Testing Events

```php
test('dispatches event', function () {
    Livewire::test(CreatePost::class)
        ->call('save')
        ->assertDispatched('post-created');
});

test('dispatches event with data', function () {
    Livewire::test(CreatePost::class)
        ->call('save')
        ->assertDispatched('post-created', fn ($name, $params) =>
            $params['postId'] === 1
        );
});
```

## Testing Component on Page

```php
test('component exists on page', function () {
    $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
});
```

## Testing with Route Parameters

```php
test('mounts with route model binding', function () {
    $post = Post::factory()->create();

    Livewire::test(EditPost::class, ['post' => $post])
        ->assertSet('post.id', $post->id)
        ->assertSet('title', $post->title);
});
```

## Testing File Uploads

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('can upload file', function () {
    Storage::fake('photos');

    $file = UploadedFile::fake()->image('photo.jpg');

    Livewire::test(UploadPhoto::class)
        ->set('photo', $file)
        ->call('save');

    Storage::disk('photos')->assertExists('photo.jpg');
});
```

## Testing View Content

```php
test('shows correct content', function () {
    Livewire::test(PostList::class)
        ->assertSee('Posts')
        ->assertDontSee('No posts found');
});

test('shows view data', function () {
    Post::factory()->create(['title' => 'Test Post']);

    Livewire::test(PostList::class)
        ->assertViewHas('posts', fn ($posts) =>
            $posts->count() === 1
        );
});
```
