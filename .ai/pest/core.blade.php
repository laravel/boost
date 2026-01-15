@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Pest

- This project uses Pest for testing. Create tests: `{{ $assist->artisanCommand('make:test --pest {name}') }}`.
- Run tests: `{{ $assist->artisanCommand('test --compact') }}` or filter: `{{ $assist->artisanCommand('test --compact --filter=testName') }}`.
- Do NOT delete tests without approval.
- **CRITICAL**: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- Use/activate 'pest-testing' whenever writing or debugging tests.
