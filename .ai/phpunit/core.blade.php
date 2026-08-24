@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
@scoped(['tests/**'])
# PHPUnit

- This project uses PHPUnit for each test. Write each test as a PHPUnit class.
- Create a test with `{{ $assist->artisanCommand('make:test --phpunit {name}') }}`.
- Convert a test to PHPUnit if you find a test that uses Pest.
- Use `LazilyRefreshDatabase` instead of `RefreshDatabase`. A test that does not use the database then does not run the migrations.
- Read the `testing-best-practices` skill to decide what to test, how to name a test, what to cover, and how to make a fake.
- Do NOT delete a test or a test file without approval. A test is part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Use an appropriate filter.
- Run one test after each change to that test.
- Run the complete suite with `{{ $assist->artisanCommand('test --compact') }}`.
- Run the tests in one file with `{{ $assist->artisanCommand('test --compact tests/Feature/ExampleTest.php') }}`.
- Run one test by name with `{{ $assist->artisanCommand('test --compact --filter=testName') }}`.
- Ask the user to run the complete suite after the tests for the feature pass.
@endscoped
