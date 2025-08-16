<?php

declare(strict_types=1);

namespace Tests\Unit\Install;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\CodeEnvironment\Windsurf;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function () {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $this->windsurf = new Windsurf($this->strategyFactory);
});

afterEach(function () {
    Mockery::close();
});

test('windsurf implements Agent interface', function () {
    expect($this->windsurf)->toBeInstanceOf(Agent::class);
});

test('windsurf does not implement McpClient interface', function () {
    expect($this->windsurf)->not->toBeInstanceOf(McpClient::class);
});

test('windsurf returns correct name', function () {
    expect($this->windsurf->name())->toBe('windsurf');
});

test('windsurf returns correct display name', function () {
    expect($this->windsurf->displayName())->toBe('Windsurf');
});

test('windsurf returns correct agent name', function () {
    expect($this->windsurf->agentName())->toBe('Cascade');
});

test('windsurf returns correct guidelines path', function () {
    expect($this->windsurf->guidelinesPath())->toBe('.windsurf/rules/laravel-boost.md');
});

test('windsurf returns correct project detection config', function () {
    $config = $this->windsurf->projectDetectionConfig();

    expect($config)->toBe([
        'paths' => ['.windsurf'],
    ]);
});

test('windsurf returns correct system detection config for Darwin', function () {
    $config = $this->windsurf->systemDetectionConfig(Platform::Darwin);

    expect($config)->toBe([
        'paths' => ['/Applications/Windsurf.app'],
    ]);
});

test('windsurf returns correct system detection config for Linux', function () {
    $config = $this->windsurf->systemDetectionConfig(Platform::Linux);

    expect($config)->toBe([
        'paths' => [
            '/opt/windsurf',
            '/usr/local/bin/windsurf',
            '~/.local/bin/windsurf',
            '/snap/bin/windsurf',
        ],
    ]);
});

test('windsurf returns correct system detection config for Windows', function () {
    $config = $this->windsurf->systemDetectionConfig(Platform::Windows);

    expect($config)->toBe([
        'paths' => [
            '%ProgramFiles%\\Windsurf',
            '%LOCALAPPDATA%\\Programs\\Windsurf',
            '%APPDATA%\\Windsurf',
        ],
    ]);
});

test('windsurf is detected as agent', function () {
    expect($this->windsurf->IsAgent())->toBeTrue();
});

test('windsurf is not detected as mcp client', function () {
    expect($this->windsurf->isMcpClient())->toBeFalse();
});

test('windsurf returns null for mcp client name', function () {
    expect($this->windsurf->mcpClientName())->toBe('Windsurf');
});

test('windsurf returns null for mcp config path', function () {
    expect($this->windsurf->mcpConfigPath())->toBeNull();
});

test('windsurf does not require frontmatter', function () {
    expect($this->windsurf->frontmatter())->toBeFalse();
});
