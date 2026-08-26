# Routing and Controller Best Practices

## Use Implicit Route Model Binding

Let Laravel resolve models from route parameters when the default lookup and missing-model behavior fit the endpoint.

Instead of manual lookup:

```php
public function show(int $id): View
{
    $post = Post::findOrFail($id);

    return view('posts.show', ['post' => $post]);
}
```

Use route model binding:

```php
public function show(Post $post): View
{
    return view('posts.show', ['post' => $post]);
}
```

## Scope Nested Bindings

Use scoped bindings when a nested resource must belong to its parent. This constrains model resolution; it does not replace authorization.

```php
Route::get('/users/{user}/posts/{post}', function (User $user, Post $post) {
    // The resolved post belongs to the resolved user.
})->scopeBindings();
```

## Use Resource Routes for Resourceful Actions

Use `Route::resource()` or `Route::apiResource()` when the endpoint follows Laravel's resource-controller actions. Define explicit routes when the behavior does not fit that vocabulary.

```php
Route::resource('posts', PostController::class);

// Alternatively, for an API-only resource:
Route::apiResource('posts', ApiPostController::class);
```

`apiResource()` omits the HTML-oriented `create` and `edit` routes. It does not itself add an `/api` prefix; that prefix comes from the application's API route configuration.

## Keep Controllers Focused on HTTP Concerns

Controllers should coordinate HTTP input, authorization, validation, an application operation, and the response. Extract substantial or reusable business logic, but do not introduce an action or service merely to satisfy an arbitrary line limit.

```php
public function store(StorePostRequest $request, CreatePostAction $create): RedirectResponse
{
    $post = $create->execute($request->validated());

    return redirect()->route('posts.show', $post);
}
```

A form request can perform validation and authorization before the controller runs. Do not repeat its rules in the controller. Keep simple, endpoint-specific validation inline when extraction would not improve reuse or clarity; see the validation rules for detailed guidance.
