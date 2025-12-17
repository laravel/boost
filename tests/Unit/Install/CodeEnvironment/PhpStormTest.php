<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('name returns phpstorm', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->name())->toBe('phpstorm');
});

test('displayName returns PhpStorm', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->displayName())->toBe('PhpStorm');
});

test('useAbsolutePathForMcp is true', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->useAbsolutePathForMcp)->toBeTrue();
});

test('systemDetectionConfig returns paths for Darwin', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->systemDetectionConfig(Platform::Darwin))
        ->toBe(['paths' => ['/Applications/PhpStorm.app']]);
});

test('systemDetectionConfig returns paths for Linux', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->systemDetectionConfig(Platform::Linux))
        ->toBe([
            'paths' => [
                '/opt/phpstorm',
                '/opt/PhpStorm*',
                '/usr/local/bin/phpstorm',
                '~/.local/share/JetBrains/Toolbox/apps/PhpStorm/ch-*',
            ],
        ]);
});

test('systemDetectionConfig returns paths for Windows', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->systemDetectionConfig(Platform::Windows))
        ->toBe([
            'paths' => [
                '%ProgramFiles%\\JetBrains\\PhpStorm*',
                '%LOCALAPPDATA%\\JetBrains\\Toolbox\\apps\\PhpStorm\\ch-*',
                '%LOCALAPPDATA%\\Programs\\PhpStorm',
            ],
        ]);
});

test('projectDetectionConfig returns idea and junie paths', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->projectDetectionConfig())
        ->toBe(['paths' => ['.idea', '.junie']]);
});

test('agentName returns Junie', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->agentName())->toBe('Junie');
});

test('mcpConfigPath returns junie mcp path', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->mcpConfigPath())->toBe('.junie/mcp/mcp.json');
});

test('guidelinesPath returns default path when no config set', function (): void {
    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->guidelinesPath())->toBe('.junie/guidelines.md');
});

test('guidelinesPath returns custom path from config', function (): void {
    config(['boost.agents.phpstorm.guidelines_path' => '.idea/guidelines.md']);

    $phpstorm = new PhpStorm($this->strategyFactory);

    expect($phpstorm->guidelinesPath())->toBe('.idea/guidelines.md');
});
