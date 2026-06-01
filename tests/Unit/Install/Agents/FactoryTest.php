<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Agents\Factory;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('name returns factory', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->name())->toBe('factory');
});

test('displayName returns Factory Droid', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->displayName())->toBe('Factory Droid');
});

test('guidelinesPath returns AGENTS.md by default', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('AGENTS.md');
});

test('skillsPath returns .factory/skills by default', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.factory/skills');
});

test('mcpConfigPath returns .factory/mcp.json by default', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.factory/mcp.json');
});

test('projectDetectionConfig detects via .factory directory', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.factory'],
    ]);
});

test('systemDetectionConfig detects droid on macOS and linux', function (Platform $platform): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->systemDetectionConfig($platform))->toBe([
        'command' => 'command -v droid',
        'paths' => ['~/.factory'],
    ]);
})->with([Platform::Darwin, Platform::Linux]);

test('systemDetectionConfig detects droid on windows', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->systemDetectionConfig(Platform::Windows))->toBe([
        'command' => 'cmd /c where droid 2>nul',
        'paths' => ['%USERPROFILE%\\.factory'],
    ]);
});

test('mcpServerConfig returns factory stdio config', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->mcpServerConfig('php', ['artisan', 'boost:mcp']))->toBe([
        'type' => 'stdio',
        'command' => 'php',
        'args' => ['artisan', 'boost:mcp'],
    ]);
});

test('mcpServerConfig includes environment variables', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->mcpServerConfig('herd', ['php', '/path/to/mcp'], ['SITE_PATH' => '/project']))->toBe([
        'type' => 'stdio',
        'command' => 'herd',
        'args' => ['php', '/path/to/mcp'],
        'env' => ['SITE_PATH' => '/project'],
    ]);
});

test('httpMcpServerConfig returns default http config', function (): void {
    $agent = new Factory($this->strategyFactory);

    expect($agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'type' => 'http',
        'url' => 'https://nightwatch.laravel.com/mcp',
    ]);
});

test('installMcp writes factory project mcp config', function (): void {
    $mcpPath = '.factory/mcp.json';

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with(dirname($mcpPath));

    File::shouldReceive('exists')
        ->once()
        ->with($mcpPath)
        ->andReturn(false);

    File::shouldReceive('put')
        ->once()
        ->withArgs(function (string $path, string $content) use ($mcpPath): bool {
            if ($path !== $mcpPath) {
                return false;
            }

            $decoded = json_decode($content, true);

            return isset($decoded['mcpServers']['laravel-boost'])
                && $decoded['mcpServers']['laravel-boost']['type'] === 'stdio'
                && $decoded['mcpServers']['laravel-boost']['command'] === 'php'
                && $decoded['mcpServers']['laravel-boost']['args'] === ['artisan', 'boost:mcp']
                && ! isset($decoded['mcpServers']['laravel-boost']['env']);
        })
        ->andReturn(true);

    $agent = new Factory($this->strategyFactory);

    expect($agent->installMcp('laravel-boost', 'php', ['artisan', 'boost:mcp']))->toBeTrue();
});
