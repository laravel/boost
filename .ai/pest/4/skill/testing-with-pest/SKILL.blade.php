---
name: testing-with-pest
description: >-
  Write tests with Pest 4 PHP framework. MUST activate when writing tests, creating unit or feature
  tests, adding assertions, testing Livewire components, browser testing, debugging test failures,
  working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion,
  coverage, or needs to verify functionality works.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Testing with Pest 4

## When to Use This Skill

Activate this skill when:
- Creating new tests (unit, feature, or browser)
- Modifying existing tests
- Debugging test failures
- Working with browser testing or smoke testing
- Writing architecture tests or visual regression tests

## Core Patterns

### Creating Tests

All tests must be written using Pest. Use `{{ $assist->artisanCommand('make:test --pest {name}') }}`.

### Test Organization
- Unit/Feature tests: `tests/Feature` and `tests/Unit` directories.
- Browser tests: `tests/Browser/` directory.
- Do NOT remove tests without approval - these are core application code.

### Basic Test Structure

<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

## Running Tests

- Run minimal tests with filter before finalizing: `{{ $assist->artisanCommand('test --compact --filter=testName') }}`.
- Run all tests: `{{ $assist->artisanCommand('test --compact') }}`.
- Run file: `{{ $assist->artisanCommand('test --compact tests/Feature/ExampleTest.php') }}`.

## Assertions

Use specific assertions (`assertSuccessful()`, `assertNotFound()`) instead of `assertStatus()`:

<code-snippet name="Pest Response Assertion" lang="php">
it('returns all', function () {
    $this->postJson('/api/docs', [])->assertSuccessful();
});
</code-snippet>

## Mocking

Import mock function before use: `use function Pest\Laravel\mock;`

## Datasets

Use datasets for repetitive tests (validation rules, etc.):

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

## Pest 4 Features

Pest 4 offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.

### Browser Testing

Browser tests run in real browsers for full integration testing:

- Browser tests live in `tests/Browser/`.
- Use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories.
- Use `RefreshDatabase` for clean state per test.
- Interact with page: click, type, scroll, select, submit, drag-and-drop, touch gestures.
- Test on multiple browsers (Chrome, Firefox, Safari) if requested.
- Test on different devices/viewports (iPhone 14 Pro, tablets) if requested.
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging.

@verbatim
<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in');

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!');

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>
@endverbatim

### Smoke Testing

Quickly validate multiple pages have no JavaScript errors:

@verbatim
<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>
@endverbatim

### Visual Regression Testing

Capture and compare screenshots to detect visual changes.

### Test Sharding

Split tests across parallel processes for faster CI runs.

### Architecture Testing

Pest 4 includes architecture testing (from Pest 3):

<code-snippet name="Architecture Test Example" lang="php">
arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtendNothing()
    ->toHaveSuffix('Controller');
</code-snippet>

## Documentation

Use `search-docs` for detailed Pest 4 patterns and documentation.

## Common Pitfalls

- Not importing `use function Pest\Laravel\mock;` before using mock
- Using `assertStatus(200)` instead of `assertSuccessful()`
- Forgetting datasets for repetitive validation tests
- Deleting tests without approval
- Forgetting `assertNoJavascriptErrors()` in browser tests
