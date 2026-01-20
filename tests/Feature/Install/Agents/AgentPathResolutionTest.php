<?php

declare(strict_types=1);

use Laravel\Boost\Install\Agents\Cursor;
use Laravel\Boost\Install\Agents\Junie;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

test('Junie returns absolute PHP_BINARY path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $junie = new Junie($strategyFactory);

    expect($junie->getPhpPath())->toBe(PHP_BINARY);
});

test('Junie returns absolute artisan path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $junie = new Junie($strategyFactory);

    $artisanPath = $junie->getArtisanPath();

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

test('Agents return absolute paths when forceAbsolutePath is true', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath(true))->toBe(PHP_BINARY);
    expect($cursor->getArtisanPath(true))->toEndWith('artisan')
        ->not->toBe('artisan');
});

test('Agents maintain relative paths when forceAbsolutePath is false', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath(false))->toBe('php');
    expect($cursor->getArtisanPath(false))->toBe('artisan');
});

test('Junie paths remain absolute regardless of forceAbsolutePath parameter', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $junie = new Junie($strategyFactory);

    // Junie always uses absolute paths, so forceAbsolutePath shouldn't change behavior
    expect($junie->getPhpPath(true))->toBe(PHP_BINARY);
    expect($junie->getPhpPath(false))->toBe(PHP_BINARY);

    $artisanPath = $junie->getArtisanPath(true);
    expect($artisanPath)->toEndWith('artisan')
        ->not->toBe('artisan');

    expect($junie->getArtisanPath(false))->toBe($artisanPath);
});
