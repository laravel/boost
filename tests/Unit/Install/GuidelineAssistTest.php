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
    $this->roster->shouldReceive('uses')->andReturn(false)->byDefault();
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

test('discovers models outside the app directory', function (): void {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    $modulesDir = $tempDir.'/modules/Blog/Models';
    mkdir($modulesDir, 0777, true);

    $modelPath = $modulesDir.'/Post.php';

    file_put_contents($modelPath, <<<'PHP'
<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model {}
PHP);

    $this->app->setBasePath($tempDir);
    mkdir($tempDir.'/app', 0777, true);
    $this->app->useAppPath($tempDir.'/app');

    $this->roster
        ->shouldReceive('uses')
        ->with(\Laravel\Roster\Enums\Approaches::MODULAR)
        ->andReturn(true);

    require_once $modelPath;

    try {
        $assist = new GuidelineAssist($this->roster, new GuidelineConfig);

        expect($assist->models())->toHaveKey('Modules\Blog\Models\Post');
    } finally {
        unlink($modelPath);
        rmdir($modulesDir);
        rmdir(dirname($modulesDir));
        rmdir(dirname($modulesDir, 2));
        rmdir($tempDir.'/app');
        rmdir($tempDir);
    }
});

test('discovers models in Modules directory (capital M)', function (): void {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    $modulesDir = $tempDir.'/Modules/Blog/Models';
    mkdir($modulesDir, 0777, true);

    $modelPath = $modulesDir.'/Article.php';

    file_put_contents($modelPath, <<<'PHP'
<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model {}
PHP);

    $this->app->setBasePath($tempDir);
    mkdir($tempDir.'/app', 0777, true);
    $this->app->useAppPath($tempDir.'/app');

    $this->roster
        ->shouldReceive('uses')
        ->with(\Laravel\Roster\Enums\Approaches::MODULAR)
        ->andReturn(true);

    require_once $modelPath;

    try {
        $assist = new GuidelineAssist($this->roster, new GuidelineConfig);

        expect($assist->models())->toHaveKey('Modules\Blog\Models\Article');
    } finally {
        unlink($modelPath);
        rmdir($modulesDir);
        rmdir(dirname($modulesDir));
        rmdir(dirname($modulesDir, 2));
        rmdir($tempDir.'/app');
        rmdir($tempDir);
    }
});

test('discovers models in app-modules directory', function (): void {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    $modulesDir = $tempDir.'/app-modules/Blog/Models';
    mkdir($modulesDir, 0777, true);

    $modelPath = $modulesDir.'/Comment.php';

    file_put_contents($modelPath, <<<'PHP'
<?php

namespace AppModules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model {}
PHP);

    $this->app->setBasePath($tempDir);
    mkdir($tempDir.'/app', 0777, true);
    $this->app->useAppPath($tempDir.'/app');

    $this->roster
        ->shouldReceive('uses')
        ->with(\Laravel\Roster\Enums\Approaches::MODULAR)
        ->andReturn(true);

    require_once $modelPath;

    try {
        $assist = new GuidelineAssist($this->roster, new GuidelineConfig);

        expect($assist->models())->toHaveKey('AppModules\Blog\Models\Comment');
    } finally {
        unlink($modelPath);
        rmdir($modulesDir);
        rmdir(dirname($modulesDir));
        rmdir(dirname($modulesDir, 2));
        rmdir($tempDir.'/app');
        rmdir($tempDir);
    }
});

test('discovers models in app directory when not modular', function (): void {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    $appModelsDir = $tempDir.'/app/Models';
    mkdir($appModelsDir, 0777, true);

    $modelPath = $appModelsDir.'/User.php';

    file_put_contents($modelPath, <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {}
PHP);

    $this->app->setBasePath($tempDir);
    $this->app->useAppPath($tempDir.'/app');

    $this->roster
        ->shouldReceive('uses')
        ->with(\Laravel\Roster\Enums\Approaches::MODULAR)
        ->andReturn(false);

    require_once $modelPath;

    try {
        $assist = new GuidelineAssist($this->roster, new GuidelineConfig);

        expect($assist->models())->toHaveKey('App\Models\User');
    } finally {
        unlink($modelPath);
        rmdir($appModelsDir);
        rmdir($tempDir.'/app');
        rmdir($tempDir);
    }
});

test('does not discover models outside app directory when not modular', function (): void {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    $modulesDir = $tempDir.'/modules/Blog/Models';
    mkdir($modulesDir, 0777, true);
    mkdir($tempDir.'/app', 0777, true);

    $modelPath = $modulesDir.'/Tag.php';

    file_put_contents($modelPath, <<<'PHP'
<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model {}
PHP);

    $this->app->setBasePath($tempDir);
    $this->app->useAppPath($tempDir.'/app');

    $this->roster
        ->shouldReceive('uses')
        ->with(\Laravel\Roster\Enums\Approaches::MODULAR)
        ->andReturn(false);

    require_once $modelPath;

    try {
        $assist = new GuidelineAssist($this->roster, new GuidelineConfig);

        expect($assist->models())->not->toHaveKey('Modules\Blog\Models\Tag');
    } finally {
        unlink($modelPath);
        rmdir($modulesDir);
        rmdir(dirname($modulesDir));
        rmdir(dirname($modulesDir, 2));
        rmdir($tempDir.'/app');
        rmdir($tempDir);
    }
});
