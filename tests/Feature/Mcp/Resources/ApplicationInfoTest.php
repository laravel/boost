<?php

declare(strict_types=1);

use JMac\Testing\Double;
use Laravel\Boost\Mcp\Boost;
use Laravel\Boost\Mcp\Resources\ApplicationInfo;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

it('returns php version, laravel version, database engine, and packages', function (): void {
    $project = Double::for(ProjectManager::class, override: true)->instance();
    mockProjectPackages($project, new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '2.0.0'),
    ]));
    $this->app->instance(ProjectManager::class, $project);

    $response = Boost::resource(ApplicationInfo::class);

    $response
        ->assertOk()
        ->assertSee([
            'php_version', PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
            'laravel_version', app()->version(),
            'database_engine', 'sqlite',
            'laravel/framework', '11.0.0',
            'pestphp/pest', '2.0.0',
        ]);
});

it('reports the driver name when the default connection has a custom name', function (): void {
    config()->set('database.connections.tenant', config('database.connections.testing'));
    config()->set('database.default', 'tenant');

    $project = Double::for(ProjectManager::class, override: true)->instance();
    mockProjectPackages($project, new PackageCollection([]));
    $this->app->instance(ProjectManager::class, $project);

    Boost::resource(ApplicationInfo::class)
        ->assertOk()
        ->assertSee(['database_engine', 'sqlite']);
});
