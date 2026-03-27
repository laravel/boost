<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Install\Agents\ClaudeCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('returns default mcp config path', function (): void {
    $agent = new ClaudeCode($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.mcp.json');
});

test('returns configured mcp config path', function (): void {
    config()->set('boost.agents.claude_code.mcp_config_path', '../.mcp.json');

    $agent = new ClaudeCode($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('../.mcp.json');
});

test('mcpServerConfig includes cwd field', function (): void {
    $agent = new ClaudeCode($this->strategyFactory);

    $config = $agent->mcpServerConfig('php', ['artisan', 'boost:mcp']);

    expect($config)->toHaveKey('command', 'php')
        ->toHaveKey('args', ['artisan', 'boost:mcp'])
        ->toHaveKey('cwd', base_path());
});

test('mcpServerConfig uses configured current_directory', function (): void {
    config()->set('boost.executable_paths.current_directory', '/Users/developer/projects/app');

    $agent = new ClaudeCode($this->strategyFactory);

    $config = $agent->mcpServerConfig('php', ['artisan', 'boost:mcp']);

    expect($config)->toHaveKey('cwd', '/Users/developer/projects/app');
});

test('mcpServerConfig filters empty args and env', function (): void {
    $agent = new ClaudeCode($this->strategyFactory);

    $config = $agent->mcpServerConfig('php', [], []);

    expect($config)->toHaveKey('command', 'php')
        ->toHaveKey('cwd')
        ->not->toHaveKey('args')
        ->not->toHaveKey('env');
});

test('httpMcpServerConfig returns default http config', function (): void {
    $agent = new ClaudeCode($this->strategyFactory);

    expect($agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'type' => 'http',
        'url' => 'https://nightwatch.laravel.com/mcp',
    ]);
});
