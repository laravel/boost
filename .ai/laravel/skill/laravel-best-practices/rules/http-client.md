# HTTP Client Best Practices

## Set Explicit Timeouts

The default response timeout is 30 seconds. Set `connectTimeout()` and `timeout()` to fit the service and the calling request or job; retries multiply total elapsed time. Put shared settings in `Http::macro()` or a dedicated client with `baseUrl()`.

## Retry Only Safe Operations

Retry connection failures, 429s, and 5xx with delays (`retry([100, 500, 1000], 0, $when)`, where `$when` checks `ConnectionException` or a `RequestException` with `serverError()` / status 429). Retry idempotent requests like `GET` freely. Retry a state-changing `POST` only if the API supports idempotency keys, and send the same key on every attempt (`withHeaders(['Idempotency-Key' => $attempt->uuid])`). A bare `Http::retry(...)->post()` to a charge endpoint can double-charge.

## Handle Errors Explicitly

`4xx` and `5xx` responses do not throw by default, so `->json()` on a failed response returns garbage. Call `->throw()` before consuming a success payload. For graceful degradation, branch on `successful()` / `notFound()` and `throw()` for everything else.

## Pool Independent Requests

`Http::pool(fn (Pool $pool) => [$pool->as('users')->get(...), ...])` runs requests concurrently. It changes timing, not error handling: apply timeouts to each request and `throw()` or inspect each response (`$responses['users']->throw()->json()`).

## Fake in Tests

Use `Http::fake()` with `Http::preventStrayRequests()` so unexpected real requests fail the test, and `Http::assertSent()` to verify requests. Also test failure paths, e.g. `Http::failedConnection()`, timeouts, and error responses.
