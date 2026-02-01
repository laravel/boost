<?php

declare(strict_types=1);

use Laravel\Boost\Install\Agents\KiloCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

test('Kilo Code returns relative php string', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $kiloCode = new KiloCode($strategyFactory);

    expect($kiloCode->getPhpPath())->toBe('php');
});

test('Kilo Code returns relative artisan path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $kiloCode = new KiloCode($strategyFactory);

    expect($kiloCode->getArtisanPath())->toBe('artisan');
});

test('Kilo Code returns absolute paths when forceAbsolutePath is true', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $kiloCode = new KiloCode($strategyFactory);

    expect($kiloCode->getPhpPath(true))->toBe(PHP_BINARY);
    expect($kiloCode->getArtisanPath(true))->toEndWith('artisan')
        ->not->toBe('artisan');
});

test('Kilo Code maintains relative paths when forceAbsolutePath is false', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $kiloCode = new KiloCode($strategyFactory);

    expect($kiloCode->getPhpPath(false))->toBe('php');
    expect($kiloCode->getArtisanPath(false))->toBe('artisan');
});
