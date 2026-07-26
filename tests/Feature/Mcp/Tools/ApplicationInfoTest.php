<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Mcp\Request;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

test('it returns application info with packages', function (): void {
    $packages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
        rosterPackage('pestphp/pest', '2.0.0'),
    ]);

    $project = Mockery::mock(ProjectManager::class);
    mockProjectPackages($project, $packages);

    $tool = new ApplicationInfo($project);
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'php_version' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
            'laravel_version' => app()->version(),
            'database_engine' => config('database.default'),
            'packages' => [
                [
                    'roster_name' => 'LARAVEL',
                    'package_name' => 'laravel/framework',
                    'version' => '11.0.0',
                ],
                [
                    'roster_name' => 'PEST',
                    'package_name' => 'pestphp/pest',
                    'version' => '2.0.0',
                ],
            ],
        ]);
});

test('it returns application info with no packages', function (): void {
    $project = Mockery::mock(ProjectManager::class);
    mockProjectPackages($project, new PackageCollection([]));

    $tool = new ApplicationInfo($project);
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'php_version' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
            'laravel_version' => app()->version(),
            'database_engine' => config('database.default'),
            'packages' => [],
        ]);
});

it('returns updated package versions when roster binding changes in container', function (): void {
    $initialPackages = new PackageCollection([
        rosterPackage('laravel/framework', '11.0.0'),
    ]);

    $project = Mockery::mock(ProjectManager::class);
    mockProjectPackages($project, $initialPackages);
    $this->app->instance(ProjectManager::class, $project);

    $tool = app(ApplicationInfo::class);
    $response = $tool->handle(new Request([]));

    expect($response)->toolJsonContent(function (array $data): void {
        expect($data)->toHaveKeys(['packages', 'php_version', 'laravel_version', 'database_engine'])
            ->and($data['packages'])->toHaveCount(1)
            ->sequence(
                fn ($package) => $package->toMatchArray(['version' => '11.0.0', 'roster_name' => 'LARAVEL']),
            );
    });

    $updatedPackages = new PackageCollection([
        rosterPackage('laravel/framework', '12.0.0'),
        rosterPackage('pestphp/pest', '3.0.0'),
    ]);

    $updatedProject = Mockery::mock(ProjectManager::class);
    mockProjectPackages($updatedProject, $updatedPackages);
    $this->app->instance(ProjectManager::class, $updatedProject);

    $tool = app(ApplicationInfo::class);
    $response = $tool->handle(new Request([]));

    expect($response)->toolJsonContent(function (array $data): void {
        expect($data)->toHaveKeys(['packages', 'php_version', 'laravel_version', 'database_engine'])
            ->and($data['packages'])->toHaveCount(2)
            ->sequence(
                fn ($package) => $package->toMatchArray(['version' => '12.0.0', 'roster_name' => 'LARAVEL']),
                fn ($package) => $package->toMatchArray(['package_name' => 'pestphp/pest', 'version' => '3.0.0']),
            );
    });
});
