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

test('uses relative paths for MCP', function (): void {
    $agent = new ClaudeCode($this->strategyFactory);

    expect($agent->useAbsolutePathForMcp())->toBeFalse();
});

test('returns relative PHP path', function (): void {
    config(['boost.executable_paths.php' => null]);

    $agent = new ClaudeCode($this->strategyFactory);

    expect($agent->getPhpPath())->toBe('php');
});

test('returns relative artisan path', function (): void {
    $agent = new ClaudeCode($this->strategyFactory);

    $artisanPath = $agent->getArtisanPath();

    expect($artisanPath)->toBe('artisan');
});

test('httpMcpServerConfig returns default http config', function (): void {
    $agent = new ClaudeCode($this->strategyFactory);

    expect($agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'type' => 'http',
        'url' => 'https://nightwatch.laravel.com/mcp',
    ]);
});
