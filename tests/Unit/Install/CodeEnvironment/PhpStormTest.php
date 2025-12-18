<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('systemDetectionConfig returns path-based detection for all platforms', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->systemDetectionConfig(Platform::Darwin))
        ->toHaveKey('paths')
        ->and($phpstorm->systemDetectionConfig(Platform::Linux))
        ->toHaveKey('paths')
        ->and($phpstorm->systemDetectionConfig(Platform::Windows))
        ->toHaveKey('paths');
});

test('guidelinesPath returns a default path when no config set', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->guidelinesPath())->toBe('.junie/guidelines.md');
});

test('guidelinesPath returns a custom path from config', function (): void {
    config(['boost.code_environments.phpstorm.guidelines_path' => '.idea/guidelines.md']);

    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->guidelinesPath())->toBe('.idea/guidelines.md');
});
