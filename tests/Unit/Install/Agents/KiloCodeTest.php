<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Agents\KiloCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('kilo code returns correct name', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent->name())->toBe('kilo_code');
});

test('kilo code returns correct display name', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent->displayName())->toBe('Kilo Code');
});

test('kilo code implements supports guidelines interface', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent)->toBeInstanceOf(SupportsGuidelines::class);
});

test('kilo code implements supports mcp interface', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent)->toBeInstanceOf(SupportsMcp::class);
});

test('kilo code implements supports skills interface', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent)->toBeInstanceOf(SupportsSkills::class);
});

test('kilo code returns correct system detection config for darwin', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Darwin);

    expect($config)->toBe([
        'command' => 'command -v kilo-code 2>/dev/null',
    ]);
});

test('kilo code returns correct system detection config for linux', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Linux);

    expect($config)->toBe([
        'command' => 'command -v kilo-code 2>/dev/null',
    ]);
});

test('kilo code returns correct system detection config for windows', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    $config = $agent->systemDetectionConfig(Platform::Windows);

    expect($config)->toBe([
        'command' => 'where kilo-code 2>nul',
    ]);
});

test('kilo code returns correct project detection config', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    $config = $agent->projectDetectionConfig();

    expect($config)->toBe([
        'paths' => ['.kilocode'],
        'files' => ['AGENTS.md'],
    ]);
});

test('kilo code returns correct mcp config path', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.kilocode/mcp.json');
});

test('kilo code returns correct guidelines path with default config', function (): void {
    config(['boost.agents.kilo_code.guidelines_path' => null]);

    $agent = new KiloCode($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('.kilocode/rules');
});

test('kilo code returns correct guidelines path with custom config', function (): void {
    config(['boost.agents.kilo_code.guidelines_path' => '.kilocode/rules']);

    $agent = new KiloCode($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('.kilocode/rules');
});

test('kilo code returns correct skills path with default config', function (): void {
    config(['boost.agents.kilo_code.skills_path' => null]);

    $agent = new KiloCode($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.kilocode/skills');
});

test('kilo code returns correct skills path with custom config', function (): void {
    config(['boost.agents.kilo_code.skills_path' => 'custom/skills']);

    $agent = new KiloCode($this->strategyFactory);

    expect($agent->skillsPath())->toBe('custom/skills');
});

test('kilo code does not use absolute path for mcp by default', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent->useAbsolutePathForMcp())->toBe(false);
});

test('kilo code returns php path correctly', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent->getPhpPath())->toBe('php');
    expect($agent->getPhpPath(true))->toBe(PHP_BINARY);
});

test('kilo code returns artisan path correctly', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent->getArtisanPath())->toBe('artisan');
    expect($agent->getArtisanPath(true))->toBe(base_path('artisan'));
});

test('kilo code returns true for frontmatter', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent->frontmatter())->toBe(true);
});

test('kilo code returns correct mcp config key', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent->mcpConfigKey())->toBe('mcpServers');
});

test('kilo code returns empty default mcp config', function (): void {
    $agent = new KiloCode($this->strategyFactory);

    expect($agent->defaultMcpConfig())->toBe([]);
});
