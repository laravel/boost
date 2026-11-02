<?php

declare(strict_types=1);

namespace Tests;

use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Mcp\Server\Registrar;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use VerifiesDoubles;

    protected function defineEnvironment($app)
    {
        $app['env'] = 'local';

        $app->singleton('mcp', Registrar::class);

        $app->useStoragePath(realpath(__DIR__.'/../workbench/storage'));
    }

    protected function getPackageProviders($app)
    {
        return [BoostServiceProvider::class];
    }
}
