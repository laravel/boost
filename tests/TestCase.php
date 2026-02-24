<?php

declare(strict_types=1);

namespace Tests;

use Composer\Autoload\ClassLoader;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Mcp\Server\Registrar;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ReflectionClass;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app)
    {
        $app['env'] = 'local';

        $app->singleton('mcp', Registrar::class);

        $app->useStoragePath(realpath(__DIR__.'/../workbench/storage'));

        $vendorDir = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 2);
        $_ENV['COMPOSER_VENDOR_DIR'] = $vendorDir;
        putenv("COMPOSER_VENDOR_DIR={$vendorDir}");
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [BoostServiceProvider::class];
    }
}
