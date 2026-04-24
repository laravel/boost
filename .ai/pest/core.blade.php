@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Pest

- This project uses Pest for testing. Create tests: `{{ $assist->artisanCommand('make:test --pest {name}') }}`.
- The `{name}` argument should not include the test suite directory. Use `{{ $assist->artisanCommand('make:test --pest SomeFeatureTest') }}` instead of `{{ $assist->artisanCommand('make:test --pest Feature/SomeFeatureTest') }}`.
- Run tests: `{{ $assist->artisanCommand('test --compact') }}` or filter: `{{ $assist->artisanCommand('test --compact --filter=testName') }}`.
- Do NOT delete tests without approval.
