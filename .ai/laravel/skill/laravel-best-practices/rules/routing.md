# Routing and Controller Best Practices

## Route Model Binding

Type-hint the model instead of calling `findOrFail($id)` when the default lookup and 404 behavior fit. Add `->scopeBindings()` to nested routes so the child must belong to its parent; this constrains resolution and does not replace authorization.

## Resource Routes

Use `Route::resource()` or `Route::apiResource()` when the endpoint follows the standard resource actions; define explicit routes otherwise. `apiResource()` omits `create` and `edit` and does not add an `/api` prefix; that comes from the API route configuration.

## One Resource per Controller

Keep each controller on one resource with the standard actions (`index`, `show`, `create`, `store`, `edit`, `update`, `destroy`). Before adding a custom action like `publish` or `archive`, consider whether it is its own resource (`PublishedPodcastController@store` / `@destroy`) with its own authorization, validation, and middleware. A custom verb is a design signal, not proof a new controller is needed. Keep the explicit action route when resource modeling would obscure the domain or conflict with project conventions. Use query parameters for simple filtering.

## Controllers Stay on HTTP

A controller coordinates input, authorization, validation, one application operation, and the response. Extract substantial or reusable business logic, not to hit a line limit. Do not repeat Form Request rules in the controller; keep simple one-off validation inline (see `validation.md`).
