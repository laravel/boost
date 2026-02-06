@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Pint Code Formatter

Only run this command when you have modified PHP files. If you only changed non-PHP files, you can skip this step.

@if($assist->supportsPintAgentFormatter())
- You must run `{{ $assist->binCommand('pint') }} --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `{{ $assist->binCommand('pint') }} --test --format agent`, simply run `{{ $assist->binCommand('pint') }} --format agent` to fix any formatting issues.
@else
- You must run `{{ $assist->binCommand('pint') }} --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `{{ $assist->binCommand('pint') }} --test`, simply run `{{ $assist->binCommand('pint') }}` to fix any formatting issues.
@endif
