<?php

declare(strict_types=1);

use Laravel\Boost\Install\CodeEnvironment\Kiro;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;

it('can detect kiro on system', function () {
    $kiro = new Kiro(app(DetectionStrategyFactory::class));

    expect($kiro->name())->toBe('kiro');
    expect($kiro->displayName())->toBe('Kiro');
    expect($kiro->mcpClientName())->toBe('Kiro');
    expect($kiro->agentName())->toBe('Kiro');
});

it('has correct system detection paths for different platforms', function () {
    $kiro = new Kiro(app(DetectionStrategyFactory::class));

    $darwinConfig = $kiro->systemDetectionConfig(Platform::Darwin);
    expect($darwinConfig['paths'])->toContain('/Applications/Kiro.app');

    $linuxConfig = $kiro->systemDetectionConfig(Platform::Linux);
    expect($linuxConfig['paths'])->toContain('/opt/kiro');

    $windowsConfig = $kiro->systemDetectionConfig(Platform::Windows);
    expect($windowsConfig['paths'])->toContain('%ProgramFiles%\\Kiro');
});

it('has correct project detection config', function () {
    $kiro = new Kiro(app(DetectionStrategyFactory::class));

    $config = $kiro->projectDetectionConfig();
    expect($config['paths'])->toContain('.kiro');
});

it('has correct mcp config path', function () {
    $kiro = new Kiro(app(DetectionStrategyFactory::class));

    expect($kiro->mcpConfigPath())->toBe('.kiro/settings/mcp.json');
});

it('has correct guidelines path', function () {
    $kiro = new Kiro(app(DetectionStrategyFactory::class));

    expect($kiro->guidelinesPath())->toBe('.kiro/steering/laravel-boost.md');
});

it('implements required contracts', function () {
    $kiro = new Kiro(app(DetectionStrategyFactory::class));

    expect($kiro)->toBeInstanceOf(\Laravel\Boost\Contracts\Agent::class);
    expect($kiro)->toBeInstanceOf(\Laravel\Boost\Contracts\McpClient::class);
});

it('uses frontmatter for guidelines', function () {
    $kiro = new Kiro(app(DetectionStrategyFactory::class));

    expect($kiro->frontmatter())->toBeTrue();
});
