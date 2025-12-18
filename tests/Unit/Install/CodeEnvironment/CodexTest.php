<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\Codex;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('systemDetectionConfig returns command-based detection for all platforms', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->systemDetectionConfig(Platform::Darwin))
        ->toHaveKey('command')
        ->and($codex->systemDetectionConfig(Platform::Linux))
        ->toHaveKey('command')
        ->and($codex->systemDetectionConfig(Platform::Windows))
        ->toHaveKey('command');
});

test('guidelinesPath returns default AGENTS.md when no config set', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->guidelinesPath())->toBe('AGENTS.md');
});

test('guidelinesPath returns a custom path from config', function (): void {
    config(['boost.code_environments.codex.guidelines_path' => 'docs/AGENTS.md']);

    $codex = new Codex($this->strategyFactory);

    expect($codex->guidelinesPath())->toBe('docs/AGENTS.md');
});
