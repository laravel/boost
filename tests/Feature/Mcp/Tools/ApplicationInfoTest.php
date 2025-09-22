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
