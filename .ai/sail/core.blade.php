@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `{{ $assist->sailBinaryPath() }} up -d` and stop them with `{{ $assist->sailBinaryPath() }} stop`.
- Open the application in the browser by running `{{ $assist->sailBinaryPath() }} open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `{{ $assist->sailBinaryPath() }}`. Examples:
    - Run Artisan Commands: `{{ $assist->artisanCommand('migrate') }}`
    - Install Composer packages: `{{ $assist->composerCommand('install') }}`
    - Execute Node commands: `{{ $assist->nodePackageManagerCommand('run dev') }}`
    - Execute PHP scripts: `{{ $assist->sailBinaryPath() }} php [script]`
- View all available Sail commands by running `{{ $assist->sailBinaryPath() }}` without arguments.
