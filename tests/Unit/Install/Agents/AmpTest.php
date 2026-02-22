<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Boost\Install\Agents\Amp;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('name returns amp', function (): void {
    $agent = new Amp($this->strategyFactory);

    expect($agent->name())->toBe('amp');
});

test('displayName returns Amp', function (): void {
    $agent = new Amp($this->strategyFactory);

    expect($agent->displayName())->toBe('Amp');
});

test('mcpInstallationStrategy returns SHELL', function (): void {
    $agent = new Amp($this->strategyFactory);

    expect($agent->mcpInstallationStrategy())->toBe(McpInstallationStrategy::SHELL);
});

test('shellMcpCommand returns amp mcp add with workspace flag and quoted command', function (): void {
    $agent = new Amp($this->strategyFactory);

    expect($agent->shellMcpCommand())->toBe('amp mcp add {key} --workspace -- "{command}" {args}');
});

test('guidelinesPath returns AGENTS.md by default', function (): void {
    $agent = new Amp($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('AGENTS.md');
});

test('skillsPath returns .agents/skills by default', function (): void {
    $agent = new Amp($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.agents/skills');
});

test('projectDetectionConfig only uses .amp directory', function (): void {
    $agent = new Amp($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.amp'],
    ]);
});

test('installMcp with env vars writes directly to settings file', function (): void {
    $settingsPath = base_path('.amp/settings.json');

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with(dirname($settingsPath));

    File::shouldReceive('exists')
        ->once()
        ->with($settingsPath)
        ->andReturn(false);

    File::shouldReceive('put')
        ->once()
        ->withArgs(function (string $path, string $content) use ($settingsPath): bool {
            if ($path !== $settingsPath) {
                return false;
            }

            $decoded = json_decode($content, true);

            return isset($decoded['amp.mcpServers']['herd'])
                && $decoded['amp.mcpServers']['herd']['command'] === 'herd'
                && $decoded['amp.mcpServers']['herd']['args'][0] === 'php'
                && $decoded['amp.mcpServers']['herd']['args'][1] === '/path/to/mcp'
                && $decoded['amp.mcpServers']['herd']['env']['SITE_PATH'] === '/project';
        })
        ->andReturn(true);

    $agent = new Amp($this->strategyFactory);

    expect($agent->installMcp('herd', 'herd php', ['/path/to/mcp'], ['SITE_PATH' => '/project']))->toBeTrue();
});

test('installMcp without env vars delegates to parent shell strategy', function (): void {
    Process::fake([
        '*' => Process::result(exitCode: 0),
    ]);

    $agent = new Amp($this->strategyFactory);

    expect($agent->installMcp('laravel-boost', 'php', ['artisan', 'boost:mcp']))->toBeTrue();

    Process::assertRan(fn ($process) => str_contains($process->command, 'amp mcp add'));
});

test('installHttpMcp runs amp mcp add with url', function (): void {
    Process::fake([
        '*' => Process::result(exitCode: 0),
    ]);

    $agent = new Amp($this->strategyFactory);

    expect($agent->installHttpMcp('nightwatch', 'https://nightwatch.laravel.com/mcp'))->toBeTrue();

    Process::assertRan('amp mcp add nightwatch --workspace https://nightwatch.laravel.com/mcp');
});
