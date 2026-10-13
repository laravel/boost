@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Fakes, Mocks, and Determinism

Control time, randomness, sleeping, and network calls, or tests fail for reasons unrelated to the code.

## Choosing How to Isolate

Fetch `https://laravel.com/framework/docs/mocking` for fakes, facade doubles, and fake assertions. Confirm each name before using it.

Pick the first that applies. A framework fake keeps the real code path; a mock replaces the dependency.

1. Framework fakes for facades (events, queues, mail, notifications, storage, HTTP client, time, sleep).
2. A fake implementation the project already provides.
3. A mock for a container-resolved contract only when the real one leaves the process or is nondeterministic.
4. The real implementation for everything else, including the database.

## Framework Fakes

@if($pest)
- Create fakes inside the test that needs them, not in a file-level `beforeEach()`.
@else
- Create fakes inside the test method that needs them, not in `setUp()`.
@endif
- Pass class names to `Event::fake()` and `Queue::fake()` when known; a bare fake can hide unexpected dispatches. Use a bare fake only when asserting the complete result, including `assertNothingPushed()`.
- One assertion per fake: dispatched, or not dispatched. Assert job or event data when it is part of the behavior.
- Use `Exceptions::fake()` to assert a reported exception. Avoid `withoutExceptionHandling()`, which changes the response under test.

Create prerequisite factory records before `Event::fake()`: a bare fake suppresses model events such as a `creating` hook that generates a UUID, producing an invalid model. Fake first only when a factory event is under test, naming that event's class.

## Mocking

Use `shouldReceive()` before the action, `shouldHaveReceived()` after it for a spy. Use `Mockery::on()` or `withArgs()` when equality cannot express the argument, such as one field of a value object.

@if($pest)
Import the helper: `use function Pest\Laravel\mock;`.
@else
Use `$this->mock(Contract::class)` to bind a mock in the container. Do not build PHPUnit mocks for classes Mockery can double.
@endif

## Outbound HTTP

Call `Http::preventStrayRequests()` so unfaked requests fail instead of reaching the network. Fake the exact endpoint per test; a bare `Http::fake()` accepts anything and hides defects.

## Time and Randomness

- Freeze or move time in every test depending on a date, period, or timestamp.
@if($pest)
- Use `freezeTime()`, `travelTo()`, `travel()`, `travelBack()`, not `Carbon::setTestNow()`.
@else
- Use `$this->freezeTime()`, `$this->travelTo()`, `$this->travel()`, `$this->travelBack()`, not `Carbon::setTestNow()`.
@endif
- Use `Str::createRandomStringsUsing()` to fix generated identifiers or slugs.
- Use `Sleep::fake()` and assert the requested sleeps.
- Restore time and randomness after each test unless the suite already does.

## Database

- Run real queries against the test database; mocking the query builder tests the mock.
- Assert exact `toArray()` keys only when the serialized shape is a contract.
- Test app behavior caused by the schema (e.g. dependents deleted by cascade), not the database engine.
- Prefer `LazilyRefreshDatabase` so tests without the database skip migrations.
