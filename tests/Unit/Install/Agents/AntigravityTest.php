<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Install\Agents\Antigravity;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

it('returns antigravity name', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->name())->toBe('antigravity');
});

it('returns Google Antigravity display name', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->displayName())->toBe('Google Antigravity');
});

it('uses FILE-based mcp installation strategy', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->mcpInstallationStrategy())->toBe(McpInstallationStrategy::FILE);
});

it('returns default mcp config path', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.agents/plugins/laravel-boost/mcp_config.json');
});

it('returns configured mcp config path', function (): void {
    config()->set('boost.agents.antigravity.mcp_config_path', '.custom/antigravity.json');

    $agent = new Antigravity($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.custom/antigravity.json');
});

it('returns default mcp config key', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->mcpConfigKey())->toBe('mcpServers');
});

it('returns default guidelines path', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('.agents/rules/boost.md');
});

it('returns configured guidelines path', function (): void {
    config()->set('boost.agents.antigravity.guidelines_path', '.custom/boost.md');

    $agent = new Antigravity($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('.custom/boost.md');
});

it('returns default skills path', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.agents/skills');
});

it('returns configured skills path', function (): void {
    config()->set('boost.agents.antigravity.skills_path', '.custom/skills');

    $agent = new Antigravity($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.custom/skills');
});

it('projectDetectionConfig detects .agents and plugin file', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.agents'],
        'files' => ['.agents/plugins/laravel-boost/plugin.json'],
    ]);
});

it('system detection uses command -v on Darwin', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Darwin);

    expect($config['command'])->toBe('command -v antigravity');
});

it('system detection uses command -v on Linux', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Linux);

    expect($config['command'])->toBe('command -v antigravity');
});

it('system detection uses where on Windows', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Windows);

    expect($config['command'])->toBe('cmd /c where antigravity 2>nul');
});

