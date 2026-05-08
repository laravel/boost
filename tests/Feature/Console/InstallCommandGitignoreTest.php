<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Boost\Console\Enums\Theme;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Cloud;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Terminal;

beforeEach(function (): void {
    $this->originalBasePath = app()->basePath();
    $this->sandboxBasePath = sys_get_temp_dir().'/boost-install-command-'.Str::uuid();

    File::ensureDirectoryExists($this->sandboxBasePath);

    app()->setBasePath($this->sandboxBasePath);

    $this->app->instance(InstallCommand::class, new class(app(AgentsDetector::class), app(Cloud::class), app(Config::class), app(Nightwatch::class), app(Sail::class), app(Terminal::class)) extends InstallCommand
    {
        protected function displayBoostHeader(string $featureName, string $projectName, ?Theme $theme = null): void {}

        protected function discoverEnvironment(): void {}

        protected function collectInstallationPreferences(): void {}

        protected function performInstallation(): void {}

        protected function outro(): void {}
    });
});

afterEach(function (): void {
    app()->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->sandboxBasePath);
});

it('appends laravel boost ignore rules to an existing gitignore', function (): void {
    File::put(base_path('.gitignore'), "vendor/\nnode_modules/");

    $this->artisan('boost:install')
        ->expectsConfirmation('Would you like to add recommended AI artifacts to .gitignore?', 'yes')
        ->expectsOutputToContain('Added Laravel Boost ignore rules to .gitignore.')
        ->assertSuccessful();

    expect(File::get(base_path('.gitignore')))->toBe(implode("\n", [
        'vendor/',
        'node_modules/',
        '',
        '# Laravel Boost',
        '.ai/generated',
        '.ai/cache',
        '.claude/',
        '.cursor/rules/generated',
        '',
    ]));
});

it('creates a gitignore file when one does not exist', function (): void {
    $this->artisan('boost:install')
        ->expectsConfirmation('Would you like to add recommended AI artifacts to .gitignore?', 'yes')
        ->expectsOutputToContain('Added Laravel Boost ignore rules to .gitignore.')
        ->assertSuccessful();

    expect(File::get(base_path('.gitignore')))->toBe(implode("\n", [
        '# Laravel Boost',
        '.ai/generated',
        '.ai/cache',
        '.claude/',
        '.cursor/rules/generated',
        '',
    ]));
});

it('avoids duplicating existing entries and only merges missing rules into the boost section', function (): void {
    File::put(base_path('.gitignore'), implode("\n", [
        'vendor/',
        '# Laravel Boost',
        '.ai/generated',
        '.claude/',
        '',
        '.cursor/rules/generated',
        '',
    ]));

    $this->artisan('boost:install')
        ->expectsConfirmation('Would you like to add recommended AI artifacts to .gitignore?', 'yes')
        ->expectsOutputToContain('Added Laravel Boost ignore rules to .gitignore.')
        ->assertSuccessful();

    expect(File::get(base_path('.gitignore')))->toBe(implode("\n", [
        'vendor/',
        '# Laravel Boost',
        '.ai/generated',
        '.claude/',
        '.ai/cache',
        '',
        '.cursor/rules/generated',
        '',
    ]));
});
