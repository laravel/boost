<?php

declare(strict_types=1);

use Laravel\Boost\Install\CodeEnvironment\Cursor;
use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

test('PhpStorm returns absolute PHP_BINARY path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStorm = new PhpStorm($strategyFactory);

    expect($phpStorm->getPhpPath())->toBe(PHP_BINARY);
});

test('PhpStorm returns absolute artisan path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStorm = new PhpStorm($strategyFactory);

    $artisanPath = $phpStorm->getArtisanPath();

    // Should be an absolute path ending with 'artisan'
    expect($artisanPath)->toEndWith('artisan')
        ->not->toBe('artisan');
});

test('Cursor returns relative php string', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath())->toBe('php');
});

test('Cursor returns relative artisan path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getArtisanPath())->toBe('artisan');
});

test('CodeEnvironment returns absolute paths when forceAbsolutePath is true', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath(true))->toBe(PHP_BINARY);
    expect($cursor->getArtisanPath(true))->toEndWith('artisan')
        ->not->toBe('artisan');
});

test('CodeEnvironment maintains relative paths when forceAbsolutePath is false', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath(false))->toBe('php');
    expect($cursor->getArtisanPath(false))->toBe('artisan');
});

test('PhpStorm paths remain absolute regardless of forceAbsolutePath parameter', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStorm = new PhpStorm($strategyFactory);

    // PhpStorm always uses absolute paths, so forceAbsolutePath shouldn't change behavior
    expect($phpStorm->getPhpPath(true))->toBe(PHP_BINARY);
    expect($phpStorm->getPhpPath(false))->toBe(PHP_BINARY);

    $artisanPath = $phpStorm->getArtisanPath(true);
    expect($artisanPath)->toEndWith('artisan')
        ->not->toBe('artisan');

    expect($phpStorm->getArtisanPath(false))->toBe($artisanPath);
});
