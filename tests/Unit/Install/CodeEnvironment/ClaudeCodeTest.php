<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\ClaudeCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('name returns claude_code', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->name())->toBe('claude_code');
});

test('displayName returns Claude Code', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->displayName())->toBe('Claude Code');
});

test('systemDetectionConfig returns command detection for Darwin', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->systemDetectionConfig(Platform::Darwin))
        ->toBe(['command' => 'command -v claude']);
});

test('systemDetectionConfig returns command detection for Linux', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->systemDetectionConfig(Platform::Linux))
        ->toBe(['command' => 'command -v claude']);
});

test('systemDetectionConfig returns command detection for Windows', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->systemDetectionConfig(Platform::Windows))
        ->toBe(['command' => 'where claude 2>nul']);
});

test('projectDetectionConfig returns paths and files', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->projectDetectionConfig())
        ->toBe([
            'paths' => ['.claude'],
            'files' => ['CLAUDE.md'],
        ]);
});

test('mcpInstallationStrategy returns FILE', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->mcpInstallationStrategy())
        ->toBe(McpInstallationStrategy::FILE);
});

test('mcpConfigPath returns .mcp.json', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->mcpConfigPath())->toBe('.mcp.json');
});

test('guidelinesPath returns default CLAUDE.md when no config set', function (): void {
    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->guidelinesPath())->toBe('CLAUDE.md');
});

test('guidelinesPath returns custom path from config', function (): void {
    config(['boost.agents.claude_code.guidelines_path' => '.claude/CLAUDE.md']);

    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->guidelinesPath())->toBe('.claude/CLAUDE.md');
});

test('guidelinesPath returns nested custom path from config', function (): void {
    config(['boost.agents.claude_code.guidelines_path' => 'docs/ai/CLAUDE.md']);

    $claude = new ClaudeCode($this->strategyFactory);

    expect($claude->guidelinesPath())->toBe('docs/ai/CLAUDE.md');
});
