<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\OpenCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('systemDetectionConfig returns command-based detection for all platforms', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->systemDetectionConfig(Platform::Darwin))
        ->toHaveKey('command')
        ->and($opencode->systemDetectionConfig(Platform::Linux))
        ->toHaveKey('command')
        ->and($opencode->systemDetectionConfig(Platform::Windows))
        ->toHaveKey('command');
});

test('mcpServerConfig returns correct structure', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->mcpServerConfig('php', ['artisan', 'boost:mcp'], ['APP_ENV' => 'local']))
        ->toBe([
            'type' => 'local',
            'enabled' => true,
            'command' => ['php', 'artisan', 'boost:mcp'],
            'environment' => ['APP_ENV' => 'local'],
        ]);
});

test('guidelinesPath returns default AGENTS.md when no config set', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->guidelinesPath())->toBe('AGENTS.md');
});

test('guidelinesPath returns a custom path from config', function (): void {
    config(['boost.code_environments.opencode.guidelines_path' => 'docs/AGENTS.md']);

    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->guidelinesPath())->toBe('docs/AGENTS.md');
});
