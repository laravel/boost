@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Pint Code Formatter

@if($assist->supportsPintAgentFormatter())
- Only run Pint when you have modified PHP files. Do not run Pint if no PHP files were modified.
- If you have modified any PHP files, you must run `{{ $assist->binCommand('pint') }} --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `{{ $assist->binCommand('pint') }} --test --format agent`, simply run `{{ $assist->binCommand('pint') }} --format agent` to fix any formatting issues.
@else
- Only run Pint when you have modified PHP files. Do not run Pint if no PHP files were modified.
- If you have modified any PHP files, you must run `{{ $assist->binCommand('pint') }} --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `{{ $assist->binCommand('pint') }} --test`, simply run `{{ $assist->binCommand('pint') }}` to fix any formatting issues.
@endif
