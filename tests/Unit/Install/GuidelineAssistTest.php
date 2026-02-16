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

test('php executable falls back to Sail when no config is set', function (): void {
    config(['boost.executable_paths.php' => null]);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->artisan())->toBe(Sail::artisanCommand());
});

test('php executable config takes precedence over Sail', function (): void {
    config(['boost.executable_paths.php' => '/usr/local/bin/php8.3']);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->artisan())->toBe('/usr/local/bin/php8.3 artisan');
});

test('composer executable falls back to Sail when no config is set', function (): void {
    config(['boost.executable_paths.composer' => null]);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    $defaultSailComposer = Sail::composerCommand();

    expect($assist->composerCommand('install'))->toBe("{$defaultSailComposer} install");
});

test('composer executable config takes precedence over Sail', function (): void {
    config(['boost.executable_paths.composer' => '/usr/local/bin/composer2']);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->composerCommand('install'))->toBe('/usr/local/bin/composer2 install');
});

test('npm executable falls back to Sail when no config is set', function (): void {
    config(['boost.executable_paths.npm' => null]);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    $expectedCommand = Sail::nodePackageManagerCommand('npm');

    expect($assist->nodePackageManagerCommand('install'))->toBe("{$expectedCommand} install");
});

test('npm executable config takes precedence over Sail', function (): void {
    config(['boost.executable_paths.npm' => '/usr/local/bin/yarn']);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->nodePackageManagerCommand('install'))->toBe('/usr/local/bin/yarn install');
});

test('npm executable falls back to npm when no config and no Sail', function (): void {
    config(['boost.executable_paths.npm' => null]);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->nodePackageManagerCommand('install'))->toBe('npm install');
});

test('vendor bin prefix falls back to Sail when no config is set', function (): void {
    config(['boost.executable_paths.vendor_bin' => null]);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    $expectedPrefix = Sail::binCommand();

    expect($assist->binCommand('pint'))->toBe("{$expectedPrefix}pint");
});

test('vendor bin prefix config takes precedence over Sail', function (): void {
    config(['boost.executable_paths.vendor_bin' => '/custom/path/']);
    $this->config->usesSail = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->binCommand('pint'))->toBe('/custom/path/pint');
});

test('vendor bin prefix falls back to vendor/bin when no config and no Sail', function (): void {
    config(['boost.executable_paths.vendor_bin' => null]);
    $this->config->usesSail = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    $expected = PHP_OS_FAMILY === 'Windows' ? 'vendor\bin\pint' : 'vendor/bin/pint';
    expect($assist->binCommand('pint'))->toBe($expected);
});

test('hasSkills property defaults to false', function (): void {
    $config = new GuidelineConfig;

    expect($config->hasSkills)->toBeFalse();
});

test('hasSkills property can be set to true', function (): void {
    $config = new GuidelineConfig;
    $config->hasSkills = true;

    expect($config->hasSkills)->toBeTrue();
});

test('hasSkillsEnabled returns false when skills are disabled', function (): void {
    $this->config->hasSkills = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->hasSkillsEnabled())->toBeFalse();
});

test('hasSkillsEnabled returns true when skills are enabled', function (): void {
    $this->config->hasSkills = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->hasSkillsEnabled())->toBeTrue();
});

test('php executable uses plain php when running inside container', function (): void {
    config(['boost.executable_paths.php' => null]);
    $this->config->usesSail = true;
    $this->config->runningInsideContainer = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->artisan())->toBe('php artisan');
});

test('composer uses plain composer when running inside container', function (): void {
    config(['boost.executable_paths.composer' => null]);
    $this->config->usesSail = true;
    $this->config->runningInsideContainer = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->composerCommand('install'))->toBe('composer install');
});

test('npm uses plain npm when running inside container', function (): void {
    config(['boost.executable_paths.npm' => null]);
    $this->config->usesSail = true;
    $this->config->runningInsideContainer = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->nodePackageManagerCommand('install'))->toBe('npm install');
});

test('vendor bin uses plain vendor/bin when running inside container', function (): void {
    config(['boost.executable_paths.vendor_bin' => null]);
    $this->config->usesSail = true;
    $this->config->runningInsideContainer = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->binCommand('pint'))->toBe('vendor/bin/pint');
});
