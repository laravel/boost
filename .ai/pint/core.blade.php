@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Laravel Pint Code Formatter

- You must run `{{ $assist->binCommand('pint') }} --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `{{ $assist->binCommand('pint') }} --test`, simply run `{{ $assist->binCommand('pint') }}` to fix any formatting issues.
