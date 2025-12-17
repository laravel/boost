<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\Codex;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('name returns codex', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->name())->toBe('codex');
});

test('displayName returns Codex', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->displayName())->toBe('Codex');
});

test('systemDetectionConfig returns command detection for Darwin', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->systemDetectionConfig(Platform::Darwin))
        ->toBe(['command' => 'which codex']);
});

test('systemDetectionConfig returns command detection for Linux', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->systemDetectionConfig(Platform::Linux))
        ->toBe(['command' => 'which codex']);
});

test('systemDetectionConfig returns command detection for Windows', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->systemDetectionConfig(Platform::Windows))
        ->toBe(['command' => 'where codex 2>nul']);
});

test('projectDetectionConfig returns paths and files', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->projectDetectionConfig())
        ->toBe([
            'paths' => ['.codex'],
            'files' => ['AGENTS.md'],
        ]);
});

test('mcpInstallationStrategy returns SHELL', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->mcpInstallationStrategy())
        ->toBe(McpInstallationStrategy::SHELL);
});

test('shellMcpCommand returns correct command template', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->shellMcpCommand())
        ->toBe('codex mcp add {key} -- {command} {args}');
});

test('guidelinesPath returns default AGENTS.md when no config set', function (): void {
    $codex = new Codex($this->strategyFactory);

    expect($codex->guidelinesPath())->toBe('AGENTS.md');
});

test('guidelinesPath returns custom path from config', function (): void {
    config(['boost.agents.codex.guidelines_path' => 'docs/AGENTS.md']);

    $codex = new Codex($this->strategyFactory);

    expect($codex->guidelinesPath())->toBe('docs/AGENTS.md');
});
