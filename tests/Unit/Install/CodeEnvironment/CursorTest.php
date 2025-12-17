<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\Cursor;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('name returns cursor', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->name())->toBe('cursor');
});

test('displayName returns Cursor', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->displayName())->toBe('Cursor');
});

test('systemDetectionConfig returns paths for Darwin', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->systemDetectionConfig(Platform::Darwin))
        ->toBe(['paths' => ['/Applications/Cursor.app']]);
});

test('systemDetectionConfig returns paths for Linux', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->systemDetectionConfig(Platform::Linux))
        ->toBe([
            'paths' => [
                '/opt/cursor',
                '/usr/local/bin/cursor',
                '~/.local/bin/cursor',
            ],
        ]);
});

test('systemDetectionConfig returns paths for Windows', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->systemDetectionConfig(Platform::Windows))
        ->toBe([
            'paths' => [
                '%ProgramFiles%\\Cursor',
                '%LOCALAPPDATA%\\Programs\\Cursor',
            ],
        ]);
});

test('projectDetectionConfig returns cursor directory', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->projectDetectionConfig())
        ->toBe(['paths' => ['.cursor']]);
});

test('mcpConfigPath returns cursor mcp.json path', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->mcpConfigPath())->toBe('.cursor/mcp.json');
});

test('frontmatter returns true', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->frontmatter())->toBeTrue();
});

test('guidelinesPath returns default path when no config set', function (): void {
    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->guidelinesPath())->toBe('.cursor/rules/laravel-boost.mdc');
});

test('guidelinesPath returns custom path from config', function (): void {
    config(['boost.agents.cursor.guidelines_path' => '.cursor/custom-rules.mdc']);

    $cursor = new Cursor($this->strategyFactory);

    expect($cursor->guidelinesPath())->toBe('.cursor/custom-rules.mdc');
});
