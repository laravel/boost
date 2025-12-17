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

test('name returns gemini', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->name())->toBe('gemini');
});

test('displayName returns Gemini', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->displayName())->toBe('Gemini');
});

test('systemDetectionConfig returns command detection for Darwin', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->systemDetectionConfig(Platform::Darwin))
        ->toBe(['command' => 'command -v gemini']);
});

test('systemDetectionConfig returns command detection for Linux', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->systemDetectionConfig(Platform::Linux))
        ->toBe(['command' => 'command -v gemini']);
});

test('systemDetectionConfig returns command detection for Windows', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->systemDetectionConfig(Platform::Windows))
        ->toBe(['command' => 'where gemini 2>nul']);
});

test('projectDetectionConfig returns paths and files', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->projectDetectionConfig())
        ->toBe([
            'paths' => ['.gemini'],
            'files' => ['GEMINI.md'],
        ]);
});

test('mcpConfigPath returns gemini settings path', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->mcpConfigPath())->toBe('.gemini/settings.json');
});

test('guidelinesPath returns default GEMINI.md when no config set', function (): void {
    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->guidelinesPath())->toBe('GEMINI.md');
});

test('guidelinesPath returns custom path from config', function (): void {
    config(['boost.agents.gemini.guidelines_path' => 'docs/GEMINI.md']);

    $gemini = new Gemini($this->strategyFactory);

    expect($gemini->guidelinesPath())->toBe('docs/GEMINI.md');
});
