<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

test('it returns application info with packages', function () {
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
    $result = $tool->handle([]);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($content) {
            expect($content['php_version'])->toBe(PHP_VERSION)
                ->and($content['laravel_version'])->toBe(app()->version())
                ->and($content['database_engine'])->toBe(config('database.default'))
                ->and($content['packages'])->toHaveCount(2)
                ->and($content['packages'][0]['roster_name'])->toBe('LARAVEL')
                ->and($content['packages'][0]['package_name'])->toBe('laravel/framework')
                ->and($content['packages'][0]['version'])->toBe('11.0.0')
                ->and($content['packages'][1]['roster_name'])->toBe('PEST')
                ->and($content['packages'][1]['package_name'])->toBe('pestphp/pest')
                ->and($content['packages'][1]['version'])->toBe('2.0.0')
                ->and($content['models'])->toBeArray()
                ->and($content['models'])->toHaveCount(2)
                ->and($content['models'])->toContain('App\\Models\\User')
                ->and($content['models'])->toContain('App\\Models\\Post');
        });
});

test('it returns application info with no packages', function () {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $guidelineAssist = Mockery::mock(GuidelineAssist::class);
    $guidelineAssist->shouldReceive('models')->andReturn([]);

    $tool = new ApplicationInfo($roster, $guidelineAssist);
    $result = $tool->handle([]);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($content) {
            expect($content['php_version'])->toBe(PHP_VERSION)
                ->and($content['laravel_version'])->toBe(app()->version())
                ->and($content['database_engine'])->toBe(config('database.default'))
                ->and($content['packages'])->toHaveCount(0)
                ->and($content['models'])->toBeArray()
                ->and($content['models'])->toHaveCount(0);
        });
});
