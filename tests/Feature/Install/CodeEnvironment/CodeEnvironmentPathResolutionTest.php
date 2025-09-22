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
