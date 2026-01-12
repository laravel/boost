<?php

declare(strict_types=1);

use Laravel\Boost\Install\ExecutableConfig;
use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Roster;
use Mockery\MockInterface;

it('uses default php artisan when no config', function (): void {
    $config = new GuidelineConfig;
    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->artisan())->toBe('php artisan');
    expect($assist->artisanCommand('migrate'))->toBe('php artisan migrate');
});

it('uses custom php and artisan paths when configured', function (): void {
    $config = new GuidelineConfig;
    $config->executables = new ExecutableConfig(
        php: '/usr/local/bin/php8.3',
        artisan: '/app/artisan',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->artisan())->toBe('/usr/local/bin/php8.3 /app/artisan');
    expect($assist->artisanCommand('migrate'))->toBe('/usr/local/bin/php8.3 /app/artisan migrate');
    expect($assist->artisanCommand('test'))->toBe('/usr/local/bin/php8.3 /app/artisan test');
});

it('custom executables override Sail for artisan commands', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true;
    $config->executables = new ExecutableConfig(
        php: '/custom/php',
        artisan: 'artisan',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->artisan())->toBe('/custom/php artisan');
    expect($assist->artisan())->not()->toContain('vendor/bin/sail');
});

it('uses Sail when no custom executables', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true;

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->artisan())->toBe('vendor/bin/sail artisan');
});

it('uses default composer when no config', function (): void {
    $config = new GuidelineConfig;
    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->composerCommand('install'))->toBe('composer install');
    expect($assist->composerCommand('require laravel/sanctum'))->toBe('composer require laravel/sanctum');
});

it('uses custom composer path when configured', function (): void {
    $config = new GuidelineConfig;
    $config->executables = new ExecutableConfig(
        composer: '/usr/local/bin/composer',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->composerCommand('install'))->toBe('/usr/local/bin/composer install');
});

it('custom composer path overrides Sail', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true;
    $config->executables = new ExecutableConfig(
        composer: '/custom/composer',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->composerCommand('install'))->toBe('/custom/composer install');
    expect($assist->composerCommand('install'))->not()->toContain('vendor/bin/sail');
});

it('uses Sail for composer when no custom config', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true;

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->composerCommand('install'))->toBe('vendor/bin/sail composer install');
});

it('uses default vendor/bin for bin commands', function (): void {
    $config = new GuidelineConfig;
    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->binCommand('pint'))->toBe('vendor/bin/pint');
    expect($assist->binCommand('pest'))->toBe('vendor/bin/pest');
    expect($assist->binCommand('phpstan'))->toBe('vendor/bin/phpstan');
});

it('uses custom vendor_bin path when configured', function (): void {
    $config = new GuidelineConfig;
    $config->executables = new ExecutableConfig(
        vendorBin: '/custom/vendor/bin',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->binCommand('pint'))->toBe('/custom/vendor/bin/pint');
    expect($assist->binCommand('pest'))->toBe('/custom/vendor/bin/pest');
    expect($assist->binCommand('phpstan'))->toBe('/custom/vendor/bin/phpstan');
});

it('custom vendor_bin overrides Sail for bin commands', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true;
    $config->executables = new ExecutableConfig(
        vendorBin: '/custom/vendor/bin',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->binCommand('pint'))->toBe('/custom/vendor/bin/pint');
    expect($assist->binCommand('pint'))->not()->toContain('vendor/bin/sail');
});

it('uses Sail for bin commands when no custom config', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true;

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->binCommand('pint'))->toBe('vendor/bin/sail bin pint');
});

it('uses detected node package manager when no custom config', function (): void {
    $config = new GuidelineConfig;

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('nodePackageManager')
        ->andReturn(NodePackageManager::PNPM);

    $assist = new GuidelineAssist($roster, $config);

    expect($assist->nodePackageManager())->toBe('pnpm');
    expect($assist->nodePackageManagerCommand('install'))->toBe('pnpm install');
    expect($assist->nodePackageManagerCommand('run dev'))->toBe('pnpm run dev');
});

it('uses custom node manager when configured', function (): void {
    $config = new GuidelineConfig;
    $config->executables = new ExecutableConfig(
        nodeManager: 'yarn',
    );

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('nodePackageManager')
        ->andReturn(NodePackageManager::NPM);

    $assist = new GuidelineAssist($roster, $config);

    expect($assist->nodePackageManager())->toBe('yarn');
    expect($assist->nodePackageManagerCommand('install'))->toBe('yarn install');
});

it('uses custom node path when configured', function (): void {
    $config = new GuidelineConfig;
    $config->executables = new ExecutableConfig(
        nodeManager: 'pnpm',
        nodePath: '/usr/local/bin/pnpm',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->nodePackageManagerCommand('install'))->toBe('/usr/local/bin/pnpm install');
    expect($assist->nodePackageManagerCommand('run build'))->toBe('/usr/local/bin/pnpm run build');
});

it('custom node path overrides Sail', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true;
    $config->executables = new ExecutableConfig(
        nodePath: '/custom/npm',
        nodeManager: 'npm',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->nodePackageManagerCommand('install'))->toBe('/custom/npm install');
    expect($assist->nodePackageManagerCommand('install'))->not()->toContain('vendor/bin/sail');
});

it('uses Sail for node commands when no custom config', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true;

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('nodePackageManager')
        ->andReturn(NodePackageManager::NPM);

    $assist = new GuidelineAssist($roster, $config);

    expect($assist->nodePackageManagerCommand('install'))->toBe('vendor/bin/sail npm install');
});

it('defaults to npm when roster returns null', function (): void {
    $config = new GuidelineConfig;

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('nodePackageManager')
        ->andReturn(null);

    $assist = new GuidelineAssist($roster, $config);

    expect($assist->nodePackageManager())->toBe('npm');
    expect($assist->nodePackageManagerCommand('install'))->toBe('npm install');
});

it('uses default sail binary path when no config', function (): void {
    $config = new GuidelineConfig;
    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->sailBinaryPath())->toBe('vendor/bin/sail');
});

it('uses custom sail path when configured', function (): void {
    $config = new GuidelineConfig;
    $config->executables = new ExecutableConfig(
        sail: '/custom/path/to/sail',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->sailBinaryPath())->toBe('/custom/path/to/sail');
});

it('partial custom config only affects configured executables', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true;
    $config->executables = new ExecutableConfig(
        php: '/custom/php',
        // artisan, composer, vendorBin, node not customized
    );

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('nodePackageManager')
        ->andReturn(NodePackageManager::NPM);

    $assist = new GuidelineAssist($roster, $config);

    // Custom php overrides Sail for artisan
    expect($assist->artisan())->toBe('/custom/php artisan');

    // But composer still defaults since executables.composer is default
    expect($assist->composerCommand('install'))->toBe('composer install');
});

it('all custom executables work together', function (): void {
    $config = new GuidelineConfig;
    $config->usesSail = true; // Should be ignored
    $config->executables = new ExecutableConfig(
        php: '/opt/php8.3',
        artisan: '/app/artisan',
        composer: '/usr/local/bin/composer',
        vendorBin: '/app/vendor/bin',
        nodeManager: 'pnpm',
        nodePath: '/usr/local/bin/pnpm',
        sail: '/custom/sail',
    );

    $roster = Mockery::mock(Roster::class);
    $assist = new GuidelineAssist($roster, $config);

    expect($assist->artisan())->toBe('/opt/php8.3 /app/artisan');
    expect($assist->artisanCommand('migrate'))->toBe('/opt/php8.3 /app/artisan migrate');
    expect($assist->composerCommand('install'))->toBe('/usr/local/bin/composer install');
    expect($assist->binCommand('pint'))->toBe('/app/vendor/bin/pint');
    expect($assist->nodePackageManager())->toBe('pnpm');
    expect($assist->nodePackageManagerCommand('install'))->toBe('/usr/local/bin/pnpm install');
    expect($assist->sailBinaryPath())->toBe('/custom/sail');

    // None should contain Sail wrapper
    expect($assist->artisan())->not()->toContain('vendor/bin/sail');
    expect($assist->composerCommand('install'))->not()->toContain('vendor/bin/sail');
    expect($assist->binCommand('pint'))->not()->toContain('vendor/bin/sail');
    expect($assist->nodePackageManagerCommand('install'))->not()->toContain('vendor/bin/sail');
});
