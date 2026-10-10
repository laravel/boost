# Error Handling Best Practices

## Where to Report and Render

Follow the project's existing pattern: `report()` / `render()` methods on the exception class, or `$exceptions->report()` / `render()` callbacks in `bootstrap/app.php`.

Default-handling rules differ. An exception's `report()` method suppresses default reporting unless it returns `false`; a report callback still allows default reporting unless it returns `false` or is chained with `stop()`. For `render()`, returning `false` defers to default rendering.

## Exceptions Not to Report

Implement `Illuminate\Contracts\Debug\ShouldntReport` (or use `dontReport()`) to keep the policy visible on the class. Explicit logging in application code is unaffected.

## Throttle High-Volume Reports

A failing integration can flood logs or error tracking. Use `throttle()` with a `Lottery` or `Limit`, choosing keys deliberately when classes, tenants, or integrations need independent limits.

## Duplicate Reports

`dontReportDuplicates()` deduplicates by exception object identity, not class or message.

## JSON for API Routes

Rendering follows content negotiation. If the API contract requires JSON regardless of `Accept`, set `shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson())`.

## Exception Context

Define `context(): array` on the exception class; Laravel merges it into the log context when reporting.
