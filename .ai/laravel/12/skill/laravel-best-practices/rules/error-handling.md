---
title: Error Handling Best Practices
impact: MEDIUM
tags: exceptions, error-handling, logging
---

# Error Handling Best Practices

## Put `report()` and `render()` on the Exception Class

Co-locate exception behavior with the exception itself instead of centralizing everything in `bootstrap/app.php`.

```php
class InvalidOrderException extends Exception
{
    public function report(): void { /* custom reporting */ }

    public function render(Request $request): Response
    {
        return response()->view('errors.invalid-order', status: 422);
    }
}
```

## Use `ShouldntReport` for Exceptions That Should Never Log

More discoverable than listing classes in `dontReport()`.

```php
class PodcastProcessingException extends Exception implements ShouldntReport {}
```

## Throttle High-Volume Exceptions

A single failing integration can flood error tracking. Use `throttle()` to rate-limit per exception type.

## Enable `dontReportDuplicates()`

Prevents the same exception instance from being logged multiple times when `report($e)` is called in multiple catch blocks.

## Force JSON Error Rendering for API Routes

Laravel auto-detects `Accept: application/json` but API clients may not set it. Explicitly declare JSON rendering for API routes.

```php
$exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
    return $request->is('api/*') || $request->expectsJson();
});
```

## Add Context to Exception Classes

Attach structured data to exceptions at the source via a `context()` method — Laravel includes it automatically in the log entry.

```php
class InvalidOrderException extends Exception
{
    public function context(): array
    {
        return ['order_id' => $this->orderId];
    }
}
```
