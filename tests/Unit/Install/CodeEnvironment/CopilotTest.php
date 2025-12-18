<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\Copilot;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('detectOnSystem always returns false', function (): void {
    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->detectOnSystem(Platform::Darwin))->toBeFalse()
        ->and($copilot->detectOnSystem(Platform::Linux))->toBeFalse()
        ->and($copilot->detectOnSystem(Platform::Windows))->toBeFalse();
});

test('guidelinesPath returns a default path when no config set', function (): void {
    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->guidelinesPath())->toBe('.github/copilot-instructions.md');
});

test('guidelinesPath returns a custom path from config', function (): void {
    config(['boost.code_environments.copilot.guidelines_path' => 'docs/copilot.md']);

    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->guidelinesPath())->toBe('docs/copilot.md');
});
