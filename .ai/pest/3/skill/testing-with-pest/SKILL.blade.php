---
name: testing-with-pest
description: >-
  Write tests with Pest 3 PHP framework. MUST activate when writing tests, creating unit or feature
  tests, adding assertions, testing Livewire components, architecture testing, debugging test failures,
  working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion,
  coverage, or needs to verify functionality works.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Testing with Pest 3

## When to Use This Skill

Activate this skill when:
- Creating new tests (unit or feature)
- Modifying existing tests
- Debugging test failures
- Working with datasets, mocking, or test organization
- Writing architecture tests

## Core Patterns

### Creating Tests

All tests must be written using Pest. Use `{{ $assist->artisanCommand('make:test --pest {name}') }}`.

### Test Organization
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Do NOT remove tests without approval - these are core application code.
- Test happy paths, failure paths, and edge cases.

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

## Pest 3 Features

### Architecture Testing

Pest 3 includes architecture testing to enforce code conventions:

<code-snippet name="Architecture Test Example" lang="php">
arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtendNothing()
    ->toHaveSuffix('Controller');

arch('models')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('no debugging')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();
</code-snippet>

### Type Coverage

Pest 3 provides improved type coverage analysis. Run with `--type-coverage` flag.

## Documentation

Use `search-docs` for detailed Pest 3 patterns and documentation.

## Common Pitfalls

- Not importing `use function Pest\Laravel\mock;` before using mock
- Using `assertStatus(200)` instead of `assertSuccessful()`
- Forgetting datasets for repetitive validation tests
- Deleting tests without approval
