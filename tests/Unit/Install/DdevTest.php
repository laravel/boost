<?php

declare(strict_types=1);

use Laravel\Boost\Install\Ddev;

it('detects ddev is installed when .ddev/config.yaml exists', function (): void {
    $configDir = base_path('.ddev');
    mkdir($configDir, 0755, true);
    file_put_contents($configDir.'/config.yaml', 'name: test');

    $ddev = new Ddev;

    expect($ddev->isInstalled())->toBeTrue();

    unlink($configDir.'/config.yaml');
    rmdir($configDir);
});

it('detects ddev is not installed when .ddev/config.yaml is missing', function (): void {
    $ddev = new Ddev;

    expect($ddev->isInstalled())->toBeFalse();
});

it('builds correct mcp command', function (): void {
    $ddev = new Ddev;

    expect($ddev->buildMcpCommand('laravel-boost'))->toBe([
        'key' => 'laravel-boost',
        'command' => 'ddev',
        'args' => ['exec', 'php', 'artisan', 'boost:mcp'],
    ]);
});

it('returns correct artisan command', function (): void {
    expect(Ddev::artisanCommand())->toBe('ddev exec php artisan');
});

it('returns correct composer command', function (): void {
    expect(Ddev::composerCommand())->toBe('ddev exec composer');
});

it('returns correct bin command', function (): void {
    expect(Ddev::binCommand())->toBe('ddev exec vendor/bin/');
});
