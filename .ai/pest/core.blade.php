@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
@scoped(['tests/**'])
## Pest

- This project uses Pest for each test. Create a test with `{{ $assist->artisanCommand('make:test --pest {name}') }}`.
- Do not put the directory of the test suite in `{name}`. Write `SomeFeatureTest`, and not `Feature/SomeFeatureTest`.
- Run the narrowest set of tests that covers the change. Use a file name, or `{{ $assist->artisanCommand('test --compact --filter=testName') }}`.
- Run the complete suite with `{{ $assist->artisanCommand('test --compact') }}`.
- Import the mock function before you use it: `use function Pest\Laravel\mock;`.
- Read the `testing-best-practices` skill to decide what to test, how to name a test, what to cover, and how to make a fake.
- Do NOT delete a test without approval.
@endscoped
