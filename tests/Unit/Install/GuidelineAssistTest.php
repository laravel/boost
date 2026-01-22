<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Sail;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->roster->shouldReceive('nodePackageManager')->andReturn(null);
    $this->roster->shouldReceive('usesVersion')->andReturn(false);

    $this->config = new GuidelineConfig;
});

test('artisan returns configured default_php_bin when not using Sail', function (): void {
    config(['boost.commands.php' => '/usr/local/bin/php8.3']);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->artisan())->toBe('/usr/local/bin/php8.3 artisan');
});

test('artisan uses php when config is set to php and not using Sail', function (): void {
    config(['boost.commands.php' => 'php']);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->artisan())->toBe('php artisan');
});

test('artisan uses config over Sail when config is set', function (): void {
    config(['boost.commands.php' => '/usr/local/bin/php8.3']);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->artisan())->toBe('/usr/local/bin/php8.3 artisan');
});

test('artisan uses Sail command when usesSail is true and config is empty', function (): void {
    config(['boost.commands.php' => null]);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->artisan())->toBe(Sail::artisanCommand());
});

test('composerCommand returns configured default_composer_bin when not using Sail', function (): void {
    config(['boost.commands.composer' => '/usr/local/bin/composer2']);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->composerCommand('install'))->toBe('/usr/local/bin/composer2 install');
});

test('composerCommand uses composer when config is set to composer and not using Sail', function (): void {
    config(['boost.commands.composer' => 'composer']);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->composerCommand('install'))->toBe('composer install');
});

test('composerCommand uses config over Sail when config is set', function (): void {
    config(['boost.commands.composer' => '/usr/local/bin/composer2']);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->composerCommand('install'))->toBe('/usr/local/bin/composer2 install');
});

test('composerCommand uses Sail command when usesSail is true and config is empty', function (): void {
    config(['boost.commands.composer' => null]);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    $defaultSailComposer = Sail::composerCommand();

    expect($assist->composerCommand('install'))->toBe("{$defaultSailComposer} install");
});

test('artisanCommand uses configured default_php_bin', function (): void {
    config(['boost.commands.php' => '/opt/php/bin/php']);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->artisanCommand('migrate'))->toBe('/opt/php/bin/php artisan migrate');
});

test('nodePackageManagerCommand returns configured npm binary when set', function (): void {
    config(['boost.commands.npm' => '/usr/local/bin/pnpm']);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->nodePackageManagerCommand('install'))->toBe('/usr/local/bin/pnpm install');
});

test('nodePackageManagerCommand uses config over Sail when config is set', function (): void {
    config(['boost.commands.npm' => '/usr/local/bin/yarn']);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->nodePackageManagerCommand('install'))->toBe('/usr/local/bin/yarn install');
});

test('nodePackageManagerCommand uses Sail command when usesSail is true and config is null', function (): void {
    config(['boost.commands.npm' => null]);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    $expectedCommand = Sail::nodePackageManagerCommand('npm');

    expect($assist->nodePackageManagerCommand('install'))->toBe("{$expectedCommand} install");
});

test('nodePackageManagerCommand uses detected package manager when config is null and not using Sail', function (): void {
    config(['boost.commands.npm' => null]);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->nodePackageManagerCommand('install'))->toBe('npm install');
});

test('binCommand returns configured vendor_bin_prefix when set', function (): void {
    config(['boost.commands.vendor_bin_prefix' => '/custom/path/']);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->binCommand('pint'))->toBe('/custom/path/pint');
});

test('binCommand uses config over Sail when config is set', function (): void {
    config(['boost.commands.vendor_bin_prefix' => '/custom/path/']);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->binCommand('pint'))->toBe('/custom/path/pint');
});

test('binCommand uses Sail command when usesSail is true and config is null', function (): void {
    config(['boost.commands.vendor_bin_prefix' => null]);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    $expectedPrefix = Sail::binCommand();

    expect($assist->binCommand('pint'))->toBe("{$expectedPrefix}pint");
});

test('binCommand uses vendor/bin when config is null and not using Sail', function (): void {
    config(['boost.commands.vendor_bin_prefix' => null]);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->binCommand('pint'))->toBe('vendor/bin/pint');
});
