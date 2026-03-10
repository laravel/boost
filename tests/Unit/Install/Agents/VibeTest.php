<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Agents\Vibe;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('name returns vibe', function (): void {
    $agent = new Vibe($this->strategyFactory);

    expect($agent->name())->toBe('vibe');
});

test('displayName returns Mistral Vibe', function (): void {
    $agent = new Vibe($this->strategyFactory);

    expect($agent->displayName())->toBe('Mistral Vibe');
});

test('mcpInstallationStrategy returns FILE', function (): void {
    $agent = new Vibe($this->strategyFactory);

    expect($agent->mcpInstallationStrategy())->toBe(McpInstallationStrategy::FILE);
});

test('mcpConfigPath returns .vibe/config.toml', function (): void {
    $agent = new Vibe($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.vibe/config.toml');
});

test('mcpConfigKey returns mcp_servers', function (): void {
    $agent = new Vibe($this->strategyFactory);

    expect($agent->mcpConfigKey())->toBe('mcp_servers');
});

test('guidelinesPath returns AGENTS.md by default', function (): void {
    $agent = new Vibe($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('AGENTS.md');
});

test('skillsPath returns .agents/skills by default', function (): void {
    $agent = new Vibe($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.agents/skills');
});

test('projectDetectionConfig uses .vibe directory and config.toml', function (): void {
    $agent = new Vibe($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.vibe'],
        'files' => ['.vibe/config.toml'],
    ]);
});

test('system detection uses command -v on Darwin', function (): void {
    $agent = new Vibe($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Darwin);

    expect($config['command'])->toBe('command -v vibe');
});

test('system detection uses command -v on Linux', function (): void {
    $agent = new Vibe($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Linux);

    expect($config['command'])->toBe('command -v vibe');
});

test('system detection uses where command on Windows', function (): void {
    $agent = new Vibe($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Windows);

    expect($config['command'])->toBe('cmd /c where vibe 2>nul');
});

test('mcpServerConfig includes transport stdio', function (): void {
    $agent = new Vibe($this->strategyFactory);

    $config = $agent->mcpServerConfig('php', ['artisan', 'boost:mcp']);

    expect($config)->toBe([
        'transport' => 'stdio',
        'command' => 'php',
        'args' => ['artisan', 'boost:mcp'],
    ]);
});

test('mcpServerConfig includes env when provided', function (): void {
    $agent = new Vibe($this->strategyFactory);

    $config = $agent->mcpServerConfig('php', ['artisan'], ['APP_ENV' => 'local']);

    expect($config)->toBe([
        'transport' => 'stdio',
        'command' => 'php',
        'args' => ['artisan'],
        'env' => ['APP_ENV' => 'local'],
    ]);
});

test('mcpServerConfig filters empty values', function (): void {
    $agent = new Vibe($this->strategyFactory);

    $config = $agent->mcpServerConfig('php', [], []);

    expect($config)->toBe([
        'transport' => 'stdio',
        'command' => 'php',
    ]);
});

test('httpMcpServerConfig returns http transport config', function (): void {
    $agent = new Vibe($this->strategyFactory);

    expect($agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'transport' => 'http',
        'url' => 'https://nightwatch.laravel.com/mcp',
    ]);
});

test('installMcp creates TOML config with [[mcp_servers]] array format', function (): void {
    $agent = new Vibe($this->strategyFactory);
    $capturedContent = '';

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with('.vibe');

    File::shouldReceive('exists')
        ->once()
        ->with('.vibe/config.toml')
        ->andReturn(false);

    File::shouldReceive('put')
        ->once()
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $agent->installMcp('laravel-boost', 'php', ['artisan', 'boost:mcp']);

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[[mcp_servers]]')
        ->and($capturedContent)->toContain('name = "laravel-boost"')
        ->and($capturedContent)->toContain('transport = "stdio"')
        ->and($capturedContent)->toContain('command = "php"')
        ->and($capturedContent)->toContain('args = ["artisan", "boost:mcp"]');
});

test('installMcp with env vars includes them in the block', function (): void {
    $agent = new Vibe($this->strategyFactory);
    $capturedContent = '';

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with('.vibe');

    File::shouldReceive('exists')
        ->once()
        ->with('.vibe/config.toml')
        ->andReturn(false);

    File::shouldReceive('put')
        ->once()
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $agent->installMcp('herd', 'herd php', ['/path/to/mcp'], ['SITE_PATH' => '/project']);

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[[mcp_servers]]')
        ->and($capturedContent)->toContain('name = "herd"')
        ->and($capturedContent)->toContain('transport = "stdio"')
        ->and($capturedContent)->toContain('command = "herd"')
        ->and($capturedContent)->toContain('SITE_PATH = "/project"');
});

test('installHttpMcp creates TOML config with http transport', function (): void {
    $agent = new Vibe($this->strategyFactory);
    $capturedContent = '';

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with('.vibe');

    File::shouldReceive('exists')
        ->once()
        ->with('.vibe/config.toml')
        ->andReturn(false);

    File::shouldReceive('put')
        ->once()
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $agent->installHttpMcp('nightwatch', 'https://nightwatch.laravel.com/mcp');

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[[mcp_servers]]')
        ->and($capturedContent)->toContain('name = "nightwatch"')
        ->and($capturedContent)->toContain('transport = "http"')
        ->and($capturedContent)->toContain('url = "https://nightwatch.laravel.com/mcp"');
});

test('installMcp preserves existing config and appends new server', function (): void {
    $agent = new Vibe($this->strategyFactory);
    $capturedContent = '';

    $existingConfig = <<<'TOML'
[[mcp_servers]]
name = "other-server"
transport = "stdio"
command = "other"
args = ["run"]
TOML;

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with('.vibe');

    File::shouldReceive('exists')
        ->once()
        ->with('.vibe/config.toml')
        ->andReturn(true);

    File::shouldReceive('size')
        ->once()
        ->with('.vibe/config.toml')
        ->andReturn(strlen($existingConfig));

    File::shouldReceive('get')
        ->once()
        ->with('.vibe/config.toml')
        ->andReturn($existingConfig);

    File::shouldReceive('put')
        ->once()
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $agent->installMcp('laravel-boost', 'php', ['artisan', 'boost:mcp']);

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('name = "other-server"')
        ->and($capturedContent)->toContain('name = "laravel-boost"');
});

test('installMcp replaces existing server with same name', function (): void {
    $agent = new Vibe($this->strategyFactory);
    $capturedContent = '';

    $existingConfig = <<<'TOML'
[[mcp_servers]]
name = "laravel-boost"
transport = "stdio"
command = "old-php"
args = ["old-artisan"]
TOML;

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with('.vibe');

    File::shouldReceive('exists')
        ->once()
        ->with('.vibe/config.toml')
        ->andReturn(true);

    File::shouldReceive('size')
        ->once()
        ->with('.vibe/config.toml')
        ->andReturn(strlen($existingConfig));

    File::shouldReceive('get')
        ->once()
        ->with('.vibe/config.toml')
        ->andReturn($existingConfig);

    File::shouldReceive('put')
        ->once()
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $agent->installMcp('laravel-boost', 'php', ['artisan', 'boost:mcp']);

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('name = "laravel-boost"')
        ->and($capturedContent)->toContain('command = "php"')
        ->and($capturedContent)->not->toContain('old-php');
});
