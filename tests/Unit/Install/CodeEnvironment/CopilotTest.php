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

test('name returns copilot', function (): void {
    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->name())->toBe('copilot');
});

test('displayName returns GitHub Copilot', function (): void {
    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->displayName())->toBe('GitHub Copilot');
});

test('systemDetectionConfig returns empty files array', function (): void {
    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->systemDetectionConfig(Platform::Darwin))
        ->toBe(['files' => []]);
});

test('projectDetectionConfig returns copilot instructions file', function (): void {
    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->projectDetectionConfig())
        ->toBe([
            'files' => ['.github/copilot-instructions.md'],
        ]);
});

test('detectOnSystem always returns false', function (): void {
    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->detectOnSystem(Platform::Darwin))->toBeFalse()
        ->and($copilot->detectOnSystem(Platform::Linux))->toBeFalse()
        ->and($copilot->detectOnSystem(Platform::Windows))->toBeFalse();
});

test('mcpClientName returns null', function (): void {
    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->mcpClientName())->toBeNull();
});

test('guidelinesPath returns default path when no config set', function (): void {
    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->guidelinesPath())->toBe('.github/copilot-instructions.md');
});

test('guidelinesPath returns custom path from config', function (): void {
    config(['boost.agents.copilot.guidelines_path' => 'docs/copilot.md']);

    $copilot = new Copilot($this->strategyFactory);

    expect($copilot->guidelinesPath())->toBe('docs/copilot.md');
});
