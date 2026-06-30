<?php

declare(strict_types=1);

use Laravel\Boost\Console\Enums\Theme;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Cloud;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Terminal;

beforeEach(function (): void {
    $this->app->instance(InstallCommand::class, new class(app(AgentsDetector::class), app(Cloud::class), app(Config::class), app(Nightwatch::class), app(Sail::class), app(Terminal::class)) extends InstallCommand
    {
        protected function displayBoostHeader(string $featureName, string $projectName, ?Theme $theme = null): void {}

        protected function discoverEnvironment(): void {}

        protected function collectInstallationPreferences(): void {}

        protected function performInstallation(): void {}

        protected function outro(): void {}
    });
});

it('shows suggested gitignore entries in interactive installs', function (): void {
    $this->artisan('boost:install')
        ->expectsOutputToContain('Suggested .gitignore entry for Boost:')
        ->expectsOutputToContain('# Laravel Boost')
        ->expectsOutputToContain('boost.json')
        ->assertSuccessful();
});

it('does not show suggested gitignore entries in non-interactive installs', function (): void {
    $this->artisan('boost:install', ['--no-interaction' => true])
        ->doesntExpectOutputToContain('Suggested .gitignore entry for Boost:')
        ->doesntExpectOutputToContain('boost.json')
        ->assertSuccessful();
});
