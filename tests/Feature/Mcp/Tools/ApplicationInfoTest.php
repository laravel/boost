<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Mcp\Request;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

test('it returns application info with packages', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '2.0.0'),
    ]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);

    $tool = new ApplicationInfo($roster);
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
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $tool = new ApplicationInfo($roster);
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
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($initialPackages);
    $this->app->instance(Roster::class, $roster);

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
        new Package(Packages::LARAVEL, 'laravel/framework', '12.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]);

    $updatedRoster = Mockery::mock(Roster::class);
    $updatedRoster->shouldReceive('packages')->andReturn($updatedPackages);
    $this->app->instance(Roster::class, $updatedRoster);

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
