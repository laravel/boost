@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
$pest5 = $assist->hasPackage('pestphp/pest', '>=5.0');
@endphp
# Test Suite Performance

These settings apply to the project and CI, not to individual tests. Read `rules/isolation.md` for choices within a test.

@if($pest)
Fetch `https://pestphp.com/docs/optimizing-tests` for Pest options that make test runs faster.
@else
Fetch `https://docs.phpunit.de/en/13.3/` for PHPUnit options that make test runs faster.
@endif
Verify each flag in the documentation before adding it to CI.

Measure before changing a setting. Find the slow test first, and apply a project-wide setting only after identifying the costly work.

## Test Environment

- Set `BCRYPT_ROUNDS=4` in `.env.testing` or in `phpunit.xml`. The default value is 12, and the hash then takes most of the time of each test that signs a user in.
- Disable XDebug. Disable pcov also, unless the run needs the coverage.
- Disable packages that perform work on every request in the test environment. Examples are Pulse, Telescope, and Nightwatch.
- Use the `WithCachedConfig` and `WithCachedRoutes` traits, so the run does not parse the configuration and the routes for every test.
- Call `withoutVite()`, or `withoutMix()`, so the framework does not resolve a built asset.

## Global Fakes

@if($pest)
Put these three calls in the base `Pest.php` of the project:
@else
Put these three calls in the `setUp()` of the base `TestCase` of the project:
@endif

- `Http::preventStrayRequests()`, because one request that reaches the network can slow the suite. This catches requests made through Laravel's HTTP client. Check direct Guzzle and cURL usage separately.
- `Sleep::fake(syncWithCarbon: true)`, so a retry and a backoff do not sleep.
- `Exceptions::fake()`, so the suite does not report an exception to an external service.

## How to Run the Suite in Parallel

@if($pest)
Run `{{ $assist->binCommand('pest --parallel') }}` to spread tests across the machine's CPU cores. Add `--processes=N` if the default count is unsuitable for the machine or CI.
@else
Run `{{ $assist->artisanCommand('test --parallel') }}`, which uses ParaTest, to spread tests across the machine's CPU cores. Add `--processes=N` if the default count is unsuitable for the machine or CI.
@endif

A parallel run gives each process a separate database. Tests must meet these conditions; a test that fails only in parallel breaks one of them:

- The test creates each record that it reads. It does not read a record that another test creates.
- The test does not depend on the order of the run.
- The test does not share a file, a cache key, or a queue with another test. Give each process a separate name for such a resource.

@if($pest5)
## How to Run Fewer Tests

Run `{{ $assist->binCommand('pest --parallel --tia') }}` to run only the tests that the recent changes affect. Pest replays the cached result of each other test.

Pest replays cached results rather than skipping unaffected tests. The cache includes each produced value and the covered lines and branches. Pest finds affected Laravel, Symfony, Livewire, and Inertia tests without configuration.

## How to Split Tests Across CI

Run `{{ $assist->binCommand('pest --update-shards') }}` to measure the time of each test. Run `{{ $assist->binCommand('pest --shard=1/4') }}` in each CI job, and change the first number for each job.

Commit `tests/.pest/shards.json` so each CI job gets the same shard and the shards remain balanced by runtime rather than test count.

@endif
## How to Find a Slow Test

@if($pest)
Run `{{ $assist->binCommand('pest --profile') }}` to list the slowest tests. Start with the ten slowest tests, because the same cause often applies to the complete suite.
@else
Run `{{ $assist->artisanCommand('test --profile') }}` to list the slowest tests. Start with the ten slowest tests, because the same cause often applies to the complete suite.
@endif

If the cause of a slow test is unclear, add an event listener or temporary log entry to identify its work.

## Common Errors

- The run loads XDebug for a test that does not need it.
- `BCRYPT_ROUNDS` keeps the default value, because the project has no `.env.testing`.
- The code under test calls the real `sleep()`, and `Sleep::fake()` then does not help.
