<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\OpenCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('name returns opencode', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->name())->toBe('opencode');
});

test('displayName returns OpenCode', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->displayName())->toBe('OpenCode');
});

test('systemDetectionConfig returns command detection for Darwin', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->systemDetectionConfig(Platform::Darwin))
        ->toBe(['command' => 'command -v opencode']);
});

test('systemDetectionConfig returns command detection for Linux', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->systemDetectionConfig(Platform::Linux))
        ->toBe(['command' => 'command -v opencode']);
});

test('systemDetectionConfig returns command detection for Windows', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->systemDetectionConfig(Platform::Windows))
        ->toBe(['command' => 'where opencode 2>nul']);
});

test('projectDetectionConfig returns files', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->projectDetectionConfig())
        ->toBe([
            'files' => ['AGENTS.md', 'opencode.json'],
        ]);
});

test('mcpInstallationStrategy returns FILE', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->mcpInstallationStrategy())
        ->toBe(McpInstallationStrategy::FILE);
});

test('mcpConfigPath returns opencode.json', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->mcpConfigPath())->toBe('opencode.json');
});

test('mcpConfigKey returns mcp', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->mcpConfigKey())->toBe('mcp');
});

test('defaultMcpConfig returns schema', function (): void {
    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->defaultMcpConfig())
        ->toBe(['$schema' => 'https://opencode.ai/config.json']);
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

test('guidelinesPath returns custom path from config', function (): void {
    config(['boost.agents.opencode.guidelines_path' => 'docs/AGENTS.md']);

    $opencode = new OpenCode($this->strategyFactory);

    expect($opencode->guidelinesPath())->toBe('docs/AGENTS.md');
});
