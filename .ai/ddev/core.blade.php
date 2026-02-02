@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# DDEV

- This project runs inside DDEV's Docker containers. You MUST execute all PHP, Artisan, and Composer commands through DDEV.
- Start services using `ddev start` and stop them with `ddev stop`.
- Always prefix PHP, Artisan, and Composer commands with `ddev exec`. Examples:
    - Run Artisan Commands: `{{ $assist->artisanCommand('migrate') }}`
    - Install Composer packages: `{{ $assist->composerCommand('install') }}`
    - Run bin scripts: `{{ $assist->binCommand('pint') }}`
- Node/frontend commands (npm, bun, etc.) run on the host, not inside DDEV.
