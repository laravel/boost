<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Install\Agents\Zed;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

it('returns zed name', function (): void {
    $agent = new Zed($this->strategyFactory);

    expect($agent->name())->toBe('zed');
});

it('returns Zed display name', function (): void {
    $agent = new Zed($this->strategyFactory);

    expect($agent->displayName())->toBe('Zed');
});

it('returns default guidelines path', function (): void {
    $agent = new Zed($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('AGENTS.md');
});

it('returns configured guidelines path', function (): void {
    config()->set('boost.agents.zed.guidelines_path', '.custom/boost.md');

    $agent = new Zed($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('.custom/boost.md');
});

it('returns default skills path', function (): void {
    $agent = new Zed($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.agents/skills');
});

it('returns configured skills path', function (): void {
    config()->set('boost.agents.zed.skills_path', '.custom/skills');

    $agent = new Zed($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.custom/skills');
});

it('returns default mcp config path', function (): void {
    $agent = new Zed($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.zed/settings.json');
});

it('returns configured mcp config path', function (): void {
    config()->set('boost.agents.zed.mcp_config_path', '.custom/zed.json');

    $agent = new Zed($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.custom/zed.json');
});

it('returns context_servers as mcp config key', function (): void {
    $agent = new Zed($this->strategyFactory);

    expect($agent->mcpConfigKey())->toBe('context_servers');
});

test('httpMcpServerConfig returns url-only config without type', function (): void {
    $agent = new Zed($this->strategyFactory);

    expect($agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'url' => 'https://nightwatch.laravel.com/mcp',
    ]);
});

it('projectDetectionConfig detects .zed path', function (): void {
    $agent = new Zed($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.zed'],
    ]);
});

it('system detection uses Zed.app path on Darwin', function (): void {
    $agent = new Zed($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Darwin);

    expect($config['paths'])->toBe(['/Applications/Zed.app']);
});

it('system detection uses command -v on Linux', function (): void {
    $agent = new Zed($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Linux);

    expect($config['command'])->toBe('command -v zed || command -v zeditor');
});

it('system detection uses where on Windows', function (): void {
    $agent = new Zed($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Windows);

    expect($config['command'])->toBe('cmd /c where zed 2>nul');
});
