<?php

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use JMac\Testing\Double;
use Laravel\Boost\Install\PestAgent;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

beforeEach(function (): void {
    $this->project = Double::for(ProjectManager::class, override: true)->instance();
});

it('is installable for a direct pest 5 dependency without the plugin', function (): void {
    mockProjectPackages($this->project, new PackageCollection([
        rosterPackage('pestphp/pest', '5.0.3', dev: true)->setDirect(),
    ]));

    expect((new PestAgent($this->project))->isInstallable())->toBeTrue();
})->skip(PHP_VERSION_ID < 80400, 'pestphp/pest-plugin-agent requires PHP 8.4');

it('is not installable', function (array $packages): void {
    mockProjectPackages($this->project, new PackageCollection($packages));

    expect((new PestAgent($this->project))->isInstallable())->toBeFalse();
})->with([
    'without pest' => [fn (): array => []],
    'with pest 4' => [fn (): array => [rosterPackage('pestphp/pest', '4.1.0', dev: true)->setDirect()]],
    'with an indirect pest' => [fn (): array => [rosterPackage('pestphp/pest', '5.0.3', dev: true)->setDirect(false)]],
    'with the plugin already installed' => [fn (): array => [
        rosterPackage('pestphp/pest', '5.0.3', dev: true)->setDirect(),
        rosterPackage(PestAgent::PACKAGE, '5.0.0', dev: true)->setDirect(),
    ]],
])->skip(PHP_VERSION_ID < 80400, 'The PHP version check would mask the package checks');

it('requires the plugin as a dev dependency without running composer scripts', function (): void {
    Process::fake();

    expect((new PestAgent($this->project))->install()->successful())->toBeTrue();

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === 'composer require pestphp/pest-plugin-agent --dev --no-scripts --no-interaction'
        && $process->path === base_path()
        && $process->timeout === PestAgent::INSTALL_TIMEOUT_SECONDS);
});

it('runs composer through sail for sail projects', function (): void {
    Process::fake();

    (new PestAgent($this->project))->install(usesSail: true);

    Process::assertRan('vendor/bin/sail composer require pestphp/pest-plugin-agent --dev --no-scripts --no-interaction');
})->skipOnWindows();

it('uses the configured composer executable', function (): void {
    config(['boost.executable_paths.composer' => '/usr/local/bin/composer']);

    Process::fake();

    (new PestAgent($this->project))->install();

    Process::assertRan('/usr/local/bin/composer require pestphp/pest-plugin-agent --dev --no-scripts --no-interaction');
});

it('reports a failed install', function (): void {
    Process::fake(['*' => Process::result(errorOutput: 'Your requirements could not be resolved', exitCode: 2)]);

    expect((new PestAgent($this->project))->install()->successful())->toBeFalse();
});
