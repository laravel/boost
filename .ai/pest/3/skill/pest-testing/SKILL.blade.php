---
name: pest-testing
description: "Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit) or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 3 features. Do not use for editing factories, seeders, migrations, controllers, models, or non-test PHP code."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Pest Testing 3

## Documentation

Use `search-docs` for detailed Pest 3 patterns and documentation.

## Basic Usage

### Creating Tests

All tests must be written using Pest. Use `{{ $assist->artisanCommand('make:test --pest {name}') }}`.

### Test Organization

- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Do NOT remove tests without approval - these are core application code.
- Test happy paths, failure paths, and edge cases.

### Basic Test Structure

Pest supports both `test()` and `it()` functions. Before writing new tests, check existing test files in the same directory to match the project's convention. Use `test()` if existing tests use `test()`, or `it()` if they use `it()`.

@boostsnippet("Basic Pest Test Example", "php")
it('is true', function () {
    expect(true)->toBeTrue();
});
@endboostsnippet

### Running Tests

- Run minimal tests with filter before finalizing: `{{ $assist->artisanCommand('test --compact --filter=testName') }}`.
- Run all tests: `{{ $assist->artisanCommand('test --compact') }}`.
- Run file: `{{ $assist->artisanCommand('test --compact tests/Feature/ExampleTest.php') }}`.

## Assertions

Use specific assertions (`assertSuccessful()`, `assertNotFound()`) instead of `assertStatus()`:

@boostsnippet("Pest Response Assertion", "php")
it('returns all', function () {
    $this->postJson('/api/docs', [])->assertSuccessful();
});
@endboostsnippet

| Use | Instead of |
|-----|------------|
| `assertSuccessful()` | `assertStatus(200)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertForbidden()` | `assertStatus(403)` |

## Mocking

Import mock function before use: `use function Pest\Laravel\mock;`

## Datasets

Use datasets for repetitive tests (validation rules, etc.):

@boostsnippet("Pest Dataset Example", "php")
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
@endboostsnippet

## Pest 3 Features

### Architecture Testing

Pest 3 includes architecture testing to enforce code conventions:

@boostsnippet("Architecture Test Example", "php")
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
@endboostsnippet

### Type Coverage

Pest 3 provides improved type coverage analysis. Run with `--type-coverage` flag.

## Common Pitfalls

- Not importing `use function Pest\Laravel\mock;` before using mock
- Using `assertStatus(200)` instead of `assertSuccessful()`
- Forgetting datasets for repetitive validation tests
- Deleting tests without approval
