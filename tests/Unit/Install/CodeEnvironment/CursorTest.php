<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\Cursor;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('systemDetectionConfig returns path-based detection for all platforms', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->systemDetectionConfig(Platform::Darwin))
        ->toHaveKey('paths')
        ->and($cursor->systemDetectionConfig(Platform::Linux))
        ->toHaveKey('paths')
        ->and($cursor->systemDetectionConfig(Platform::Windows))
        ->toHaveKey('paths');
});

test('guidelinesPath returns a default path when no config is set', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->guidelinesPath())->toBe('.cursor/rules/laravel-boost.mdc');
});

test('guidelinesPath returns a custom path from config', function (): void {
    config(['boost.code_environments.cursor.guidelines_path' => '.cursor/custom-rules.mdc']);

    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->guidelinesPath())->toBe('.cursor/custom-rules.mdc');
});
