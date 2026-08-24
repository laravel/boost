@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
$pest5 = $assist->hasPackage('pestphp/pest', '>=5.0');
@endphp
# The Performance of the Suite

These settings apply to the project and to the CI. They are not a change to one test. Read `rules/isolation.md` for the choices inside a test.

## The Environment

- Set `BCRYPT_ROUNDS=4` in `.env.testing` or in `phpunit.xml`. The default value is 12, and the hash then takes most of the time of each test that signs a user in.
- Set `APP_DEBUG=false` in the test environment, so the framework does not collect debug information.
- Disable XDebug. Disable pcov also, unless the run needs the coverage.
- Disable each package that does work on every request in the test environment. Examples are Pulse, Telescope, and Nightwatch.
- Use the `WithCachedConfig` and `WithCachedRoutes` traits, so the run does not parse the configuration and the routes for every test.
- Call `withoutVite()`, or `withoutMix()`, so the framework does not resolve a built asset.

## The Global Fakes

@if($pest)
Put these three calls in the base `Pest.php` of the project:
@else
Put these three calls in the `setUp()` of the base `TestCase` of the project:
@endif

- `Http::preventStrayRequests()`, because one slow request that reaches the network adds time to every test. This call catches a request that goes through the HTTP client of Laravel. Check each direct use of Guzzle or of cURL separately.
- `Sleep::fake(syncWithCarbon: true)`, so a retry and a backoff do not sleep.
- `Exceptions::fake()`, so the suite does not report an exception to an external service.

@if($pest5)
## How to Run Fewer Tests

Run `{{ $assist->binCommand('pest --parallel --tia') }}` to run only the tests that the recent changes affect. Pest replays the cached result of each other test.

A test that Pest replays is not a test that Pest skips. The cache holds each value that the test produced, and this includes the covered lines and the covered branches. Pest finds the affected tests for Laravel, Symfony, Livewire, and Inertia without a configuration.

## How to Split the Tests Across the CI

Run `{{ $assist->binCommand('pest --update-shards') }}` to measure the time of each test. Run `{{ $assist->binCommand('pest --shard=1/4') }}` in each job of the CI, and change the first number for each job.

Commit `tests/.pest/shards.json` to the repository. Each job of the CI then gets the same shard, and the shards stay balanced by time and not by the number of tests.

@endif
## How to Find a Slow Test

@if($pest)
Run `{{ $assist->binCommand('pest --profile') }}` to list the slowest tests. Start with the ten slowest tests, because the same cause often applies to the complete suite.
@else
Run `{{ $assist->artisanCommand('test --profile') }}` to list the slowest tests. Start with the ten slowest tests, because the same cause often applies to the complete suite.
@endif

If the cause of a slow test is not clear, add a listener for the events, or add a temporary log entry, to find the work that the test does.

## Common Errors

- The run loads XDebug for a test that does not need it.
- `BCRYPT_ROUNDS` keeps the default value, because the project has no `.env.testing`.
- The code under test calls the real `sleep()`, and `Sleep::fake()` then does not help.
