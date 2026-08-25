@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$pest = $assist->hasPackage('pestphp/pest');
@endphp
# Fakes, Mocks, and Determinism

A test that uses the real time, the real randomness, a real sleep, or a real network call can fail for a reason that is not in the code under test. Control each of these four items.

## How to Isolate a Dependency

Fetch `https://laravel.com/framework/docs/mocking` for the fakes, the facade doubles, and the assertions that each fake gives. Confirm the name before you use it.

Name the dependency, then take the first option that fits it. A fake of the framework keeps the real code path, and a mock replaces it.

1. Use the fake of the framework for a facade, such as the events, the queue, the mail, the notifications, the storage, the HTTP client, the time, and the sleep.
2. Use the fake implementation of the project for a service of the project, if such a fake exists.
3. Use a mock for a contract that the container resolves, and only when the real implementation leaves the process or is not deterministic.
4. Use the real implementation for everything else, and for the database.

## The Fakes

@if($pest)
- Make each fake inside the test that needs the fake. Do not make a fake in a `beforeEach()` at the top of the file.
@else
- Make each fake inside the test method that needs the fake. Do not make a fake in `setUp()`.
@endif
- Give the class names to `Event::fake()` and to `Queue::fake()` if you know the classes that the code dispatches. A fake with no class names hides a dispatch that you do not expect.
- Use a fake with no class names only if the test asserts the complete result, and includes `assertNothingPushed()`.
- Write one assertion for each fake. The assertion states that the code dispatches the item, or that the code does not dispatch the item.
- Assert the data of a job or of an event if that data is part of the behavior.
- Use `Exceptions::fake()` to assert that the application reports the correct exception. Do not use `withoutExceptionHandling()`, because it changes the response under test.

Create the prerequisite factory records before you call `Event::fake()`. A factory uses the model events, such as a `creating` hook that makes a UUID, and a fake with no class names stops these events and makes an incorrect model. Call the fake first only when a factory event is the behavior under test, and give it the class names of that event.

## The Mocks

Use `shouldReceive()` before the action to declare an expectation. Use `shouldHaveReceived()` after the action for a spy. Use `Mockery::on()` or `withArgs()` if an equality check cannot state the expected argument, such as a check of one field of a value object.

@if($pest)
Import the mock function before you use it: `use function Pest\Laravel\mock;`.
@else
Use `$this->mock(Contract::class)` to put a mock in the container. Do not build a PHPUnit mock for a class that Mockery can double, because the project uses Mockery.
@endif

## The Outbound HTTP

Call `Http::preventStrayRequests()`. A request that has no fake then fails, and does not reach the network.

Fake the exact endpoint that each test uses. Do not call `Http::fake()` with no endpoint, because it accepts a request that the test does not expect, and a defect can make such a request.

## The Time and the Randomness

- Freeze the time or move the time in each test that depends on a date, a period, or a timestamp.
@if($pest)
- Use the framework helpers `freezeTime()`, `travelTo()`, `travel()`, and `travelBack()`. Do not call `Carbon::setTestNow()`.
@else
- Use the framework helpers `$this->freezeTime()`, `$this->travelTo()`, `$this->travel()`, and `$this->travelBack()`. Do not call `Carbon::setTestNow()`.
@endif
- Use `Str::createRandomStringsUsing()` to fix a generated string, if the test asserts an identifier or a slug.
- Use `Sleep::fake()` instead of a real sleep, and assert the sleeps that the code requests.
- Restore the time and the randomness after each test, if the suite does not restore them for every test.

## The Database

- Run the real query against the real records in the test database. Do not mock the query builder, because the test then asserts the mock.
- Assert the exact keys of `toArray()` if the shape of the serialized model is a contract. The test then fails when the model exposes a new attribute.
- Test the effect of the schema on the application, such as a cascade that deletes the dependent records. Do not test that the cascade of the database works.
- Use `LazilyRefreshDatabase` instead of `RefreshDatabase`. A test that does not use the database then does not run the migrations.
