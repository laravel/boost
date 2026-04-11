<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->fixtureNamespace = 'BoostFixture'.bin2hex(random_bytes(6));
    $this->fixtureDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-guideline-assist-'.bin2hex(random_bytes(6));
    $this->fixtureClass = $this->fixtureNamespace.'\\Models\\Gadget';

    mkdir($this->fixtureDir.'/Models', 0777, true);
    file_put_contents(
        $this->fixtureDir.'/Models/Gadget.php',
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$this->fixtureNamespace}\\Models;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\nclass Gadget extends Model\n{\n}\n"
    );

    $loader = null;

    foreach (spl_autoload_functions() as $fn) {
        if (is_array($fn) && $fn[0] instanceof ClassLoader) {
            $loader = $fn[0];

            break;
        }
    }

    $loader->addPsr4($this->fixtureNamespace.'\\', $this->fixtureDir);

    $classesProperty = new ReflectionProperty(GuidelineAssist::class, 'classes');
    $classesProperty->setValue(null, []);

    $namespaceProperty = new ReflectionProperty($this->app, 'namespace');
    $namespaceProperty->setValue($this->app, $this->fixtureNamespace.'\\');

    $this->app->useAppPath($this->fixtureDir);

    $this->roster = Mockery::mock(Roster::class);
    $this->roster->shouldReceive('nodePackageManager')->andReturn(NodePackageManager::NPM)->byDefault();
    $this->roster->shouldReceive('usesVersion')->andReturn(false)->byDefault();
});

afterEach(function (): void {
    $classesProperty = new ReflectionProperty(GuidelineAssist::class, 'classes');
    $classesProperty->setValue(null, []);

    if (is_dir($this->fixtureDir)) {
        @unlink($this->fixtureDir.'/Models/Gadget.php');
        @rmdir($this->fixtureDir.'/Models');
        @rmdir($this->fixtureDir);
    }
});

test('discover() finds models that have not been pre-loaded', function (): void {
    expect(class_exists($this->fixtureClass, false))->toBeFalse();

    $assist = new GuidelineAssist($this->roster, new GuidelineConfig);

    expect($assist->models())->toHaveKey($this->fixtureClass);
});
