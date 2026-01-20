<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Install\Agents\Junie;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('systemDetectionConfig returns path-based detection for all platforms', function (): void {
    $junie = new Junie($this->strategyFactory);

    expect($junie->systemDetectionConfig(Platform::Darwin))
        ->toHaveKey('paths')
        ->and($junie->systemDetectionConfig(Platform::Linux))
        ->toHaveKey('paths')
        ->and($junie->systemDetectionConfig(Platform::Windows))
        ->toHaveKey('paths');
});

test('guidelinesPath returns a default path when no config is set', function (): void {
    $junie = new Junie($this->strategyFactory);

    expect($junie->guidelinesPath())->toBe('.junie/guidelines.md');
});

test('guidelinesPath returns a custom path from config', function (): void {
    config(['boost.agents.junie.guidelines_path' => '.idea/guidelines.md']);

    $junie = new Junie($this->strategyFactory);

    expect($junie->guidelinesPath())->toBe('.idea/guidelines.md');
});
