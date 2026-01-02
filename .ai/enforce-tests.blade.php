@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `{{ $assist->artisanCommand('test --compact') }}` with a specific filename or filter.
