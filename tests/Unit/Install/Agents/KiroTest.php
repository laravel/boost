<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Install\Agents\Kiro;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('guidelinesPath returns .kiro/steering/boost.md by default', function (): void {
    $agent = new Kiro($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('.kiro/steering/boost.md');
});

test('skillsPath returns .kiro/skills by default', function (): void {
    $agent = new Kiro($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.kiro/skills');
});

test('mcpConfigPath returns .kiro/settings/mcp.json by default', function (): void {
    $agent = new Kiro($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.kiro/settings/mcp.json');
});

test('projectDetectionConfig detects via .kiro directory', function (): void {
    $agent = new Kiro($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.kiro'],
    ]);
});
