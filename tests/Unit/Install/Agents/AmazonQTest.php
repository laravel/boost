<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Install\Agents\AmazonQ;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('name returns amazonq', function (): void {
    $agent = new AmazonQ($this->strategyFactory);

    expect($agent->name())->toBe('amazonq');
});

test('displayName returns Amazon Q Developer', function (): void {
    $agent = new AmazonQ($this->strategyFactory);

    expect($agent->displayName())->toBe('Amazon Q Developer');
});

test('guidelinesPath returns default path', function (): void {
    $agent = new AmazonQ($this->strategyFactory);

    expect($agent->guidelinesPath())->toBe('.amazonq/rules/guidelines.md');
});

test('skillsPath returns default path', function (): void {
    $agent = new AmazonQ($this->strategyFactory);

    expect($agent->skillsPath())->toBe('.amazonq/rules/skills');
});

test('projectDetectionConfig detects .amazonq directory', function (): void {
    $agent = new AmazonQ($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.amazonq'],
    ]);
});
