<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\ClaudeCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('systemDetectionConfig returns command-based detection for all platforms', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->systemDetectionConfig(Platform::Darwin))
        ->toHaveKey('command')
        ->and($claude->systemDetectionConfig(Platform::Linux))
        ->toHaveKey('command')
        ->and($claude->systemDetectionConfig(Platform::Windows))
        ->toHaveKey('command');
});

test('guidelinesPath returns default CLAUDE.md when no config set', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->guidelinesPath())->toBe('CLAUDE.md');
});

test('guidelinesPath returns a custom path from config', function (): void {
    config(['boost.code_environments.claude_code.guidelines_path' => '.claude/CLAUDE.md']);

    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->guidelinesPath())->toBe('.claude/CLAUDE.md');
});

test('guidelinesPath returns a nested custom path from config', function (): void {
    config(['boost.code_environments.claude_code.guidelines_path' => 'docs/ai/CLAUDE.md']);

    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->guidelinesPath())->toBe('docs/ai/CLAUDE.md');
});
