<?php

declare(strict_types=1);

use Laravel\Boost\Install\Agents\Cursor;
use Laravel\Boost\Install\Agents\Junie;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

test('Junie returns absolute PHP_BINARY path', function (): void {
    config(['boost.executables.php' => null]);
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
    config(['boost.executables.php' => null]);
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath())->toBe('php');
});

test('Cursor uses configured default_php_bin when not forcing absolute path', function (): void {
    config(['boost.executables.php' => '/custom/path/to/php']);

    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath())->toBe('/custom/path/to/php');
});

test('Cursor uses config even when forceAbsolutePath is true', function (): void {
    config(['boost.executables.php' => '/custom/path/to/php']);

    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath(true))->toBe('/custom/path/to/php');
});

test('Cursor uses PHP_BINARY when forceAbsolutePath is true and config is empty', function (): void {
    config(['boost.executables.php' => null]);

    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath(true))->toBe(PHP_BINARY);
});

test('Cursor returns relative artisan path', function (): void {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getArtisanPath())->toBe('artisan');
});

test('Agents return absolute paths when forceAbsolutePath is true and config is empty', function (): void {
    config(['boost.executables.php' => null]);
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath(true))->toBe(PHP_BINARY)
        ->and($cursor->getArtisanPath(true))->toEndWith('artisan')
        ->not->toBe('artisan');
});

test('Agents maintain relative paths when forceAbsolutePath is false and config is empty', function (): void {
    config(['boost.executables.php' => null]);
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    expect($cursor->getPhpPath())->toBe('php')
        ->and($cursor->getArtisanPath())->toBe('artisan');
});

test('Junie paths remain absolute regardless of forceAbsolutePath parameter', function (): void {
    config(['boost.executables.php' => null]);
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $junie = new Junie($strategyFactory);

    // Junie always uses absolute paths, so forceAbsolutePath shouldn't change behavior
    expect($junie->getPhpPath(true))->toBe(PHP_BINARY)
        ->and($junie->getPhpPath())->toBe(PHP_BINARY);

    $artisanPath = $junie->getArtisanPath(true);
    expect($artisanPath)->toEndWith('artisan')
        ->not->toBe('artisan')
        ->and($junie->getArtisanPath())->toBe($artisanPath);
});

test('Junie uses config when configured', function (): void {
    config(['boost.executables.php' => '/custom/php']);
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $junie = new Junie($strategyFactory);

    // Config takes precedence over useAbsolutePathForMcp
    expect($junie->getPhpPath(true))->toBe('/custom/php');
    expect($junie->getPhpPath(false))->toBe('/custom/php');
});
