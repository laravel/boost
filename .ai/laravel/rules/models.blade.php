---
paths:
  - "app/Models/**"
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `{{ $assist->artisanCommand('make:model --help') }}` to check the available options.
