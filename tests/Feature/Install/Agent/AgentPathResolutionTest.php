<?php

declare(strict_types=1);

use Laravel\Boost\Install\Agents\Cursor;
use Laravel\Boost\Install\Agents\PhpStorm;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

test('PhpStorm returns the absolute PHP_BINARY path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStorm = new PhpStorm($strategyFactory);

    expect($phpStorm->getPhpPath())->toBe(PHP_BINARY);
});

test('PhpStorm returns an absolute artisan path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStorm = new PhpStorm($strategyFactory);

    $artisanPath = $phpStorm->getArtisanPath();

    expect($artisanPath)->toEndWith('artisan')
        ->and($artisanPath)->not()->toBe('artisan');
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

test('Agent returns absolute paths when forceAbsolutePath is true', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath(true))->toBe(PHP_BINARY)
        ->and($cursor->getArtisanPath(true))->toEndWith('artisan')
        ->and($cursor->getArtisanPath(true))->not()->toBe('artisan');
});

test('Agent maintains relative paths when forceAbsolutePath is false', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath(false))->toBe('php')
        ->and($cursor->getArtisanPath(false))->toBe('artisan');
});

test('PhpStorm paths remain absolute regardless of forceAbsolutePath parameter', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStorm = new PhpStorm($strategyFactory);

    expect($phpStorm->getPhpPath(true))->toBe(PHP_BINARY)
        ->and($phpStorm->getPhpPath())->toBe(PHP_BINARY);

    $artisanPath = $phpStorm->getArtisanPath(true);
    expect($artisanPath)->toEndWith('artisan')
        ->and($artisanPath)->not()->toBe('artisan')
        ->and($phpStorm->getArtisanPath())->toBe($artisanPath);

});
