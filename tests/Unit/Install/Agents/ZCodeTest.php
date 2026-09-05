<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Agents\ZCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('returns correct name', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->name())->toBe('zcode');
});

test('returns correct display name', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->displayName())->toBe('ZCode');
});

test('uses FILE-based MCP installation strategy', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->mcpInstallationStrategy())->toBe(McpInstallationStrategy::FILE);
});

test('returns correct MCP config path', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.zcode/config.json');
});

test('returns configured MCP config path', function (): void {
    config()->set('boost.agents.zcode.mcp_config_path', '.custom/zcode.json');

    $agent = new ZCode($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.custom/zcode.json');
});

test('returns correct MCP config key', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->mcpConfigKey())->toBe('mcp.servers');
});

test('builds MCP server config with empty env', function (): void {
    $agent = new ZCode($this->strategyFactory);

    $config = $agent->mcpServerConfig('php', ['artisan', 'boost:mcp']);

    expect($config)->toBe([
        'command' => 'php',
        'args' => ['artisan', 'boost:mcp'],
        'env' => [],
    ]);
});

test('builds MCP server config with env when provided', function (): void {
    $agent = new ZCode($this->strategyFactory);

    $config = $agent->mcpServerConfig('php', ['artisan'], ['APP_ENV' => 'local']);

    expect($config)->toBe([
        'command' => 'php',
        'args' => ['artisan'],
        'env' => ['APP_ENV' => 'local'],
    ]);
});

test('httpMcpServerConfig returns type and url config', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'type' => 'http',
        'url' => 'https://nightwatch.laravel.com/mcp',
    ]);
});

test('projectDetectionConfig only uses .zcode dir and config.json', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.zcode'],
        'files' => ['.zcode/config.json'],
    ]);
});

test('detectInProject returns false when only AGENTS.md exists', function (): void {
    $agent = new ZCode(new DetectionStrategyFactory(app()));
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost_zcode_'.uniqid();
    mkdir($tempDir);
    touch($tempDir.DIRECTORY_SEPARATOR.'AGENTS.md');

    try {
        expect($agent->detectInProject($tempDir))->toBeFalse();
    } finally {
        unlink($tempDir.DIRECTORY_SEPARATOR.'AGENTS.md');
        rmdir($tempDir);
    }
});

test('returns correct guidelines path', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('AGENTS.md');
});

test('returns configured guidelines path', function (): void {
    config()->set('boost.agents.zcode.guidelines_path', '.custom/AGENTS.md');

    $agent = new ZCode($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('.custom/AGENTS.md');
});

test('returns correct skills path', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.zcode/skills');
});

test('returns configured skills path', function (): void {
    config()->set('boost.agents.zcode.skills_path', '.custom/skills');

    $agent = new ZCode($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.custom/skills');
});

test('system detection uses ~/.zcode on Darwin', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->systemDetectionConfig(Platform::Darwin))->toBe([
        'paths' => ['~/.zcode'],
    ]);
});

test('system detection uses ~/.zcode on Linux', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->systemDetectionConfig(Platform::Linux))->toBe([
        'paths' => ['~/.zcode'],
    ]);
});

test('system detection uses USERPROFILE on Windows', function (): void {
    $agent = new ZCode($this->strategyFactory);

    expect($agent->systemDetectionConfig(Platform::Windows))->toBe([
        'paths' => ['%USERPROFILE%\\.zcode'],
    ]);
});

test('installMcp creates nested JSON config file', function (): void {
    $agent = new ZCode($this->strategyFactory);
    $capturedContent = '';

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with('.zcode');

    File::shouldReceive('exists')
        ->once()
        ->with('.zcode/config.json')
        ->andReturn(false);

    File::shouldReceive('put')
        ->once()
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $agent->installMcp('laravel-boost', 'php', ['artisan', 'boost:mcp']);

    $decoded = json_decode((string) $capturedContent, true);

    expect($result)->toBeTrue()
        ->and($decoded['mcp']['servers']['laravel-boost']['command'])->toBe('php')
        ->and($decoded['mcp']['servers']['laravel-boost']['args'])->toBe(['artisan', 'boost:mcp']);
});
