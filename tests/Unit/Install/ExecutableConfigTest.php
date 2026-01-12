<?php

declare(strict_types=1);

use Laravel\Boost\Install\ExecutableConfig;

it('creates ExecutableConfig with default values', function (): void {
    $config = new ExecutableConfig;

    expect($config->php)->toBe('php');
    expect($config->artisan)->toBe('artisan');
    expect($config->composer)->toBe('composer');
    expect($config->sail)->toBe('vendor/bin/sail');
    expect($config->vendorBin)->toBe('vendor/bin');
    expect($config->nodeManager)->toBeNull();
    expect($config->nodePath)->toBeNull();
});

it('creates ExecutableConfig with custom values', function (): void {
    $config = new ExecutableConfig(
        php: '/usr/local/bin/php8.3',
        artisan: '/app/artisan',
        composer: '/usr/local/bin/composer',
        sail: '/custom/sail',
        vendorBin: '/app/vendor/bin',
        nodeManager: 'pnpm',
        nodePath: '/usr/local/bin/pnpm',
    );

    expect($config->php)->toBe('/usr/local/bin/php8.3');
    expect($config->artisan)->toBe('/app/artisan');
    expect($config->composer)->toBe('/usr/local/bin/composer');
    expect($config->sail)->toBe('/custom/sail');
    expect($config->vendorBin)->toBe('/app/vendor/bin');
    expect($config->nodeManager)->toBe('pnpm');
    expect($config->nodePath)->toBe('/usr/local/bin/pnpm');
});

it('creates ExecutableConfig from array with all values', function (): void {
    $array = [
        'php' => '/usr/bin/php8.3',
        'artisan' => '/app/artisan',
        'composer' => '/usr/local/bin/composer',
        'sail' => '/custom/sail',
        'vendor_bin' => '/app/vendor/bin',
        'node' => [
            'manager' => 'pnpm',
            'path' => '/usr/local/bin/pnpm',
        ],
    ];

    $config = ExecutableConfig::fromConfig($array);

    expect($config->php)->toBe('/usr/bin/php8.3');
    expect($config->artisan)->toBe('/app/artisan');
    expect($config->composer)->toBe('/usr/local/bin/composer');
    expect($config->sail)->toBe('/custom/sail');
    expect($config->vendorBin)->toBe('/app/vendor/bin');
    expect($config->nodeManager)->toBe('pnpm');
    expect($config->nodePath)->toBe('/usr/local/bin/pnpm');
});

it('creates ExecutableConfig from array with missing values using defaults', function (): void {
    $array = [
        'php' => '/custom/php',
    ];

    $config = ExecutableConfig::fromConfig($array);

    expect($config->php)->toBe('/custom/php');
    expect($config->artisan)->toBe('artisan');
    expect($config->composer)->toBe('composer');
    expect($config->sail)->toBe('vendor/bin/sail');
    expect($config->vendorBin)->toBe('vendor/bin');
    expect($config->nodeManager)->toBeNull();
    expect($config->nodePath)->toBeNull();
});

it('creates ExecutableConfig from empty array using all defaults', function (): void {
    $config = ExecutableConfig::fromConfig([]);

    expect($config->php)->toBe('php');
    expect($config->artisan)->toBe('artisan');
    expect($config->composer)->toBe('composer');
    expect($config->sail)->toBe('vendor/bin/sail');
    expect($config->vendorBin)->toBe('vendor/bin');
    expect($config->nodeManager)->toBeNull();
    expect($config->nodePath)->toBeNull();
});

it('converts ExecutableConfig to array', function (): void {
    $config = new ExecutableConfig(
        php: '/usr/bin/php',
        artisan: 'artisan',
        composer: 'composer',
        sail: 'vendor/bin/sail',
        vendorBin: 'vendor/bin',
        nodeManager: 'pnpm',
        nodePath: '/usr/local/bin/pnpm',
    );

    $array = $config->toArray();

    expect($array)->toEqual([
        'php' => '/usr/bin/php',
        'artisan' => 'artisan',
        'composer' => 'composer',
        'sail' => 'vendor/bin/sail',
        'vendor_bin' => 'vendor/bin',
        'node' => [
            'manager' => 'pnpm',
            'path' => '/usr/local/bin/pnpm',
        ],
    ]);
});

it('detects no custom paths when all values are defaults', function (): void {
    $config = new ExecutableConfig;

    expect($config->hasCustomPaths())->toBeFalse();
});

it('detects custom php path', function (): void {
    $config = new ExecutableConfig(php: '/custom/php');

    expect($config->hasCustomPaths())->toBeTrue();
});

it('detects custom artisan path', function (): void {
    $config = new ExecutableConfig(artisan: '/custom/artisan');

    expect($config->hasCustomPaths())->toBeTrue();
});

it('detects custom composer path', function (): void {
    $config = new ExecutableConfig(composer: '/custom/composer');

    expect($config->hasCustomPaths())->toBeTrue();
});

it('detects custom vendor bin path', function (): void {
    $config = new ExecutableConfig(vendorBin: '/custom/vendor/bin');

    expect($config->hasCustomPaths())->toBeTrue();
});

it('detects custom node path', function (): void {
    $config = new ExecutableConfig(nodePath: '/usr/local/bin/pnpm');

    expect($config->hasCustomPaths())->toBeTrue();
});

it('does not detect custom paths when only sail is changed', function (): void {
    $config = new ExecutableConfig(sail: '/custom/sail');

    expect($config->hasCustomPaths())->toBeFalse();
});

it('does not detect custom paths when only node manager is set', function (): void {
    $config = new ExecutableConfig(nodeManager: 'pnpm');

    expect($config->hasCustomPaths())->toBeFalse();
});

it('handles partial configuration correctly', function (): void {
    $array = [
        'php' => '/custom/php',
        'vendor_bin' => '/custom/vendor/bin',
        'node' => [
            'manager' => 'pnpm',
            'path' => null,
        ],
    ];

    $config = ExecutableConfig::fromConfig($array);

    expect($config->php)->toBe('/custom/php');
    expect($config->artisan)->toBe('artisan');
    expect($config->composer)->toBe('composer');
    expect($config->vendorBin)->toBe('/custom/vendor/bin');
    expect($config->nodeManager)->toBe('pnpm');
    expect($config->nodePath)->toBeNull();
    expect($config->hasCustomPaths())->toBeTrue();
});
