<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineAssist;
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

    $guidelineAssist = Mockery::mock(GuidelineAssist::class);
    $guidelineAssist->shouldReceive('models')->andReturn([
        'App\\Models\\User' => '/app/Models/User.php',
        'App\\Models\\Post' => '/app/Models/Post.php',
    ]);

    $tool = new ApplicationInfo($roster, $guidelineAssist);
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'php_version' => PHP_VERSION,
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
            'models' => [
                'App\\Models\\User',
                'App\\Models\\Post',
            ],
        ]);
});

test('it returns application info with no packages', function (): void {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $guidelineAssist = Mockery::mock(GuidelineAssist::class);
    $guidelineAssist->shouldReceive('models')->andReturn([]);

    $tool = new ApplicationInfo($roster, $guidelineAssist);
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_engine' => config('database.default'),
            'packages' => [],
            'models' => [],
        ]);
});

it('returns updated package versions when roster binding changes in container', function (): void {
    $initialPackages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($initialPackages);
    $this->app->instance(Roster::class, $roster);

    $guidelineAssist = Mockery::mock(GuidelineAssist::class);
    $guidelineAssist->shouldReceive('models')->andReturn([]);
    $this->app->instance(GuidelineAssist::class, $guidelineAssist);

    $tool = app(ApplicationInfo::class);
    $response = $tool->handle(new Request([]));

    expect($response)->toolJsonContent(function (array $data): void {
        expect($data)->toHaveKeys(['packages', 'php_version', 'laravel_version', 'database_engine', 'models'])
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
        expect($data)->toHaveKeys(['packages', 'php_version', 'laravel_version', 'database_engine', 'models'])
            ->and($data['packages'])->toHaveCount(2)
            ->sequence(
                fn ($package) => $package->toMatchArray(['version' => '12.0.0', 'roster_name' => 'LARAVEL']),
                fn ($package) => $package->toMatchArray(['package_name' => 'pestphp/pest', 'version' => '3.0.0']),
            );
    });
});

it('extracts model class names from guideline assist paths and returns them as array', function (): void {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn(new PackageCollection([]));
    $this->app->instance(Roster::class, $roster);

    $guidelineAssist = Mockery::mock(GuidelineAssist::class);
    $guidelineAssist->shouldReceive('models')->andReturn([
        'App\\Models\\User' => '/app/Models/User.php',
        'App\\Models\\Order' => '/app/Models/Order.php',
    ]);
    $this->app->instance(GuidelineAssist::class, $guidelineAssist);

    $tool = app(ApplicationInfo::class);
    $response = $tool->handle(new Request([]));

    expect($response)->toolJsonContent(function (array $data): void {
        expect($data)->toHaveKey('models')
            ->and($data['models'])->toHaveCount(2)
            ->each->toBeString()
            ->and($data['models'])->toBe(['App\\Models\\User', 'App\\Models\\Order']);
    });
});
