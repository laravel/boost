@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
$pest5 = $assist->hasPackage('pestphp/pest', '>=5.0');
$phpunit = $assist->project->php()->package('phpunit/phpunit');
$phpunitDocs = 'the PHPUnit '.($phpunit ? $phpunit->version().' ' : '').'documentation at `https://phpunit.de/documentation.html`';
@endphp
# Test Suite Performance

These are project and CI settings, not per-test choices (see `rules/isolation.md` for those). Measure first: find the slow tests, then apply project-wide settings.

@if($pest)
Fetch `https://pestphp.com/docs/optimizing-tests` for speed options.
@else
Fetch {{ $phpunitDocs }} for speed options.
@endif
Verify each flag in the docs before adding it to CI.

## Test Environment

- Set `BCRYPT_ROUNDS=4` in `.env.testing` or `phpunit.xml`; the default 12 dominates tests that sign users in.
- Disable XDebug, and pcov unless coverage is needed.
- Disable per-request packages (Pulse, Telescope, Nightwatch) in testing.
- Use `WithCachedConfig` and `WithCachedRoutes` so config and routes are not parsed per test.
- Call `withoutVite()` or `withoutMix()` to skip asset resolution.

## Global Fakes

@if($pest)
Put these in the base `Pest.php`:
@else
Put these in the base `TestCase`'s `setUp()`:
@endif

- `Http::preventStrayRequests()` catches Laravel HTTP client calls; check direct Guzzle and cURL separately.
- `Sleep::fake(syncWithCarbon: true)` so retries and backoff do not sleep.
- `Exceptions::fake()` so exceptions are not reported externally.

## Parallel Runs

@if($pest)
Run `{{ $assist->binCommand('pest --parallel') }}`; add `--processes=N` to override the default count.
@else
Run `{{ $assist->artisanCommand('test --parallel') }}` (ParaTest); add `--processes=N` to override the default count.
@endif

Each process gets its own database. A test failing only in parallel violates one of these:

- It creates every record it reads.
- It does not depend on run order.
- It shares no file, cache key, or queue with other tests; name such resources per process.

@if($pest5)
## Running Fewer Tests

`{{ $assist->binCommand('pest --parallel --tia') }}` runs only tests affected by recent changes and replays cached results for the rest (including values and covered lines). Laravel, Symfony, Livewire, and Inertia tests are detected without configuration.

## Splitting Across CI

Run `{{ $assist->binCommand('pest --update-shards') }}` to record test times, then `{{ $assist->binCommand('pest --shard=1/4') }}` per CI job. Commit `tests/.pest/shards.json` so shards stay balanced by runtime, not test count.

@endif
## Finding Slow Tests

@if($pest)
Run `{{ $assist->binCommand('pest --profile') }}` and start with the ten slowest; the same cause often applies suite-wide.
@else
Run `{{ $assist->artisanCommand('test --profile') }}` and start with the ten slowest; the same cause often applies suite-wide.
@endif
If the cause is unclear, add a temporary listener or log entry.

## Common Errors

- XDebug loaded when not needed.
- Default `BCRYPT_ROUNDS` because there is no `.env.testing`.
- Code calling real `sleep()`, which `Sleep::fake()` does not cover.
