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

    expect($assist->binCommand('pint'))->toBe('vendor/bin/pint');
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

test('enumContents returns empty string when app directory does not exist', function (): void {
    $sentinel = ['app-path-isnt-a-directory' => sys_get_temp_dir()];

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn($sentinel);

    expect($assist->enumContents())->toBe('');
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

test('hasMcpEnabled returns false when MCP is disabled', function (): void {
    $this->config->hasMcp = false;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->hasMcpEnabled())->toBeFalse();
});

test('hasMcpEnabled returns true when MCP is enabled', function (): void {
    $this->config->hasMcp = true;

    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->hasMcpEnabled())->toBeTrue();
});

test('appPath returns default app path', function (): void {
    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->appPath())->toBe('app');
    expect($assist->appPath('path/to/file.php'))->toBe('app/path/to/file.php');
});

test('appPath returns customized path', function (): void {
    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    app()->useAppPath('src');

    expect($assist->appPath())->toBe('src');
    expect($assist->appPath('path/to/file.php'))->toBe('src/path/to/file.php');
})->after(fn () => app()->useAppPath('app'));

test('appPath always uses forward-slash separators for guideline output', function (): void {
    // Guideline templates interpolate display-friendly paths. On Windows, app_path()
    // concatenates with DIRECTORY_SEPARATOR ('\'), so without normalization
    // appPath('Http/Kernel.php') would return "app\Http/Kernel.php" — mixed and
    // visually broken in generated CLAUDE.md files. The output must always use '/'.
    $assist = Mockery::mock(GuidelineAssist::class, [$this->roster, $this->config])->makePartial();
    $assist->shouldAllowMockingProtectedMethods();
    $assist->shouldReceive('discover')->andReturn([]);

    expect($assist->appPath('Http/Kernel.php'))->toBe('app/Http/Kernel.php')
        ->and($assist->appPath('Console/Commands/'))->toBe('app/Console/Commands/')
        ->and($assist->appPath())->not->toContain('\\');
});
