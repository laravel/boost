<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Install\Agents\Antigravity;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

it('returns antigravity name', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->name())->toBe('antigravity');
});

it('returns Antigravity display name', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->displayName())->toBe('Antigravity');
});

it('returns default mcp config path', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.agents/mcp_config.json');
});

it('returns configured mcp config path', function (): void {
    config()->set('boost.agents.antigravity.mcp_config_path', '.gemini/mcp.json');

    $agent = new Antigravity($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.gemini/mcp.json');
});

it('returns default guidelines path', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('AGENTS.md');
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

it('projectDetectionConfig detects .agents path and mcp_config.json file', function (): void {
    $agent = new Antigravity($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.agents'],
        'files' => ['.agents/mcp_config.json'],
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
