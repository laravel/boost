<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Agents\Codex;
use Laravel\Boost\Install\Contracts\DetectionStrategy;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $this->strategy = Mockery::mock(DetectionStrategy::class);
});

test('returns correct name', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->name())->toBe('codex');
});

test('returns correct display name', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->displayName())->toBe('Codex');
});

test('uses FILE-based MCP installation strategy', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->mcpInstallationStrategy())->toBe(McpInstallationStrategy::FILE);
});

test('returns correct MCP config path', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->mcpConfigPath())->toBe('.codex/config.toml');
});

test('returns correct MCP config key', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->mcpConfigKey())->toBe('mcp_servers');
});

test('builds MCP server config with cwd field', function (): void {
    $codex = new Codex($this->strategyFactory);

    $config = $codex->mcpServerConfig('php', ['artisan', 'boost:mcp']);

    expect($config)->toHaveKey('command', 'php')
        ->toHaveKey('args', ['artisan', 'boost:mcp'])
        ->toHaveKey('cwd');
});

test('builds MCP server config with env when provided', function (): void {
    $codex = new Codex($this->strategyFactory);

    $config = $codex->mcpServerConfig('php', ['artisan'], ['APP_ENV' => 'local']);

    expect($config)->toHaveKey('command', 'php')
        ->toHaveKey('args', ['artisan'])
        ->toHaveKey('cwd')
        ->toHaveKey('env', ['APP_ENV' => 'local']);
});

test('filters empty values from server config', function (): void {
    $codex = new Codex($this->strategyFactory);

    $config = $codex->mcpServerConfig('php', [], []);

    expect($config)->toHaveKey('command', 'php')
        ->toHaveKey('cwd')
        ->not->toHaveKey('args')
        ->not->toHaveKey('env');
});

test('includes config.toml in project detection', function (): void {
    $codex = new Codex($this->strategyFactory);

    $detection = $codex->projectDetectionConfig();

    expect($detection['files'])->toContain('.codex/config.toml')
        ->toContain('AGENTS.md');
    expect($detection['paths'])->toContain('.codex');
});

test('returns correct guidelines path', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->guidelinesPath())->toBe('AGENTS.md');
});

test('returns correct skills path', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->skillsPath())->toBe('.agents/skills');
});

test('httpMcpServerConfig returns url-only config', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'url' => 'https://nightwatch.laravel.com/mcp',
    ]);
});

test('system detection uses which command on Darwin', function (): void {
    $codex = new Codex($this->strategyFactory);

    $config = $codex->systemDetectionConfig(Platform::Darwin);

    expect($config['command'])->toBe('which codex');
});

test('system detection uses which command on Linux', function (): void {
    $codex = new Codex($this->strategyFactory);

    $config = $codex->systemDetectionConfig(Platform::Linux);

    expect($config['command'])->toBe('which codex');
});

test('system detection uses where command on Windows', function (): void {
    $codex = new Codex($this->strategyFactory);

    $config = $codex->systemDetectionConfig(Platform::Windows);

    expect($config['command'])->toBe('cmd /c where codex 2>nul');
});

test('installMcp creates TOML config file', function (): void {
    $codex = new Codex($this->strategyFactory);
    $capturedContent = '';

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with('.codex');

    File::shouldReceive('exists')
        ->once()
        ->with('.codex/config.toml')
        ->andReturn(false);

    File::shouldReceive('put')
        ->once()
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $codex->installMcp('laravel_boost', 'php', ['artisan', 'boost:mcp']);

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.laravel_boost]')
        ->and($capturedContent)->toContain('command = "php"')
        ->and($capturedContent)->toContain('args = ["artisan", "boost:mcp"]')
        ->and($capturedContent)->toContain('cwd = ');
});
