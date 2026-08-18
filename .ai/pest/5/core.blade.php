@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
@if($assist->hasLocallyEnabledPestTia())
## Pest TIA

- Pest 5 Test Impact Analysis is enabled for local runs through `pest()->tia()->locally()` in `tests/Pest.php`.
- For routine verification, run `{{ $assist->artisanCommand('test --compact') }}`. TIA automatically executes affected tests and replays unaffected results.
- Preserve the project's existing parallel-testing convention when running these commands. TIA does not require `--parallel`, and `--dirty` is not a substitute for TIA.
- After large refactors or when the dependency graph appears stale, rebuild it with `{{ $assist->phpCommand('./vendor/bin/pest --compact --tia --fresh') }}`.
- Before finalizing substantial or high-risk changes, run the complete suite without TIA using `{{ $assist->phpCommand('./vendor/bin/pest --compact --no-tia') }}`.
- Normal CI verification must execute the complete suite without TIA. A dedicated job that records a shared TIA baseline is the only exception.
- Recording a TIA baseline requires PCOV or Xdebug.
@endif
