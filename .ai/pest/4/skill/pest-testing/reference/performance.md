# Test Performance Reference

Environment and tooling optimizations for faster Laravel test suites. These are project-level or CI-level settings — not per-test code changes.

## Environment and config

- **Set `BCRYPT_ROUNDS=4`** in `.env.testing` (or `phpunit.xml`). Default is 12 and hashing dominates auth tests.
- **Disable XDebug.** Disable pcov too at scale unless you specifically need coverage.
- **Disable background packages** in the testing environment: Pulse, Telescope, Nightwatch, and similar 3rd-party packages that do work on every request/command.
- **Use `WithCachedConfig` and `WithCachedRoutes` traits** to avoid re-parsing config and routes on every test.
- **Call `withoutVite()` (or `withoutMix()`)** in your test setup so the framework does not try to resolve built assets.
- **`Http::preventingStrayRequests()`** in your base `Pest.php`. A single slow stray request can punish every test. Note this only catches requests made through Laravel's HTTP client. Audit direct Guzzle / cURL usage separately.
- **`Sleep::fake(syncWithCarbon: true)`** in your base `Pest.php` so retries and backoffs do not actually sleep.
- **`Exceptions::fake()`** to make sure you are not reporting to Flare/Sentry/Bugsnag from within tests.

## Profile and diagnose

- **`./vendor/bin/pest --profile`** prints the slowest tests per shard. Start there. Patterns in the top-10 often apply suite-wide.
- When a slow test is mysterious, instrument event listeners or add temporary logging to find the unexpected work happening behind the scenes.

## Quick reference

| Area | Action |
|------|--------|
| Hashing | `BCRYPT_ROUNDS=4` |
| Debuggers | Disable XDebug, disable pcov |
| Background pkgs | Disable Pulse, Telescope, Nightwatch |
| Config / routes | `WithCachedConfig`, `WithCachedRoutes` |
| Assets | `withoutVite()` / `withoutMix()` |
| HTTP | `Http::preventingStrayRequests()` |
| Sleep | `Sleep::fake(syncWithCarbon: true)` |
| Exceptions | `Exceptions::fake()` |
| Profiling | `pest --profile` |

## Common environment mistakes

- Running the suite with XDebug loaded "just in case" a test fails.
- Leaving `BCRYPT_ROUNDS` at the default because `.env.testing` was never created.
- Calling real `sleep()` or using Carbon in production code, which prevents `Sleep::fake()` from helping.
