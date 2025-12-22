<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\Gemini;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('systemDetectionConfig returns command-based detection for all platforms', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->systemDetectionConfig(Platform::Darwin))
        ->toHaveKey('command')
        ->and($gemini->systemDetectionConfig(Platform::Linux))
        ->toHaveKey('command')
        ->and($gemini->systemDetectionConfig(Platform::Windows))
        ->toHaveKey('command');
});

test('guidelinesPath returns default GEMINI.md when no config set', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->guidelinesPath())->toBe('GEMINI.md');
});

test('guidelinesPath returns a custom path from config', function (): void {
    config(['boost.code_environments.gemini.guidelines_path' => 'docs/GEMINI.md']);

    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->guidelinesPath())->toBe('docs/GEMINI.md');
});
