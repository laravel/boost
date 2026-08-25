@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
@scoped(['tests/**'])
# Pest

- This project uses Pest for each test. Create a test with `{{ $assist->artisanCommand('make:test --pest {name}') }}`.
- Do not put the directory of the test suite in `{name}`. Write `SomeFeatureTest`, and not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill to decide what to test, how to name a test, what to cover, how to isolate a dependency, and how to review a test.
- Do NOT delete a test or a test file without approval. A test is part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Add a file path, or `--filter=testName`, to `{{ $assist->artisanCommand('test --compact') }}`.
- Run one test again after each change to that test.
- Run `{{ $assist->binCommand('pest') }}` to call the test runner directly. It takes the same file path and the same `--filter=testName`.
- Ask the user to run the complete suite with `{{ $assist->artisanCommand('test --compact') }}` after the tests for the feature pass.
@endscoped
