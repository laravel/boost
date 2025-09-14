<?php

declare(strict_types=1);

use Laravel\Boost\Install\CodeEnvironment\Codex;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

test('Codex returns relative php and artisan paths', function () {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $codex = new Codex($strategyFactory);

    expect($codex->getPhpPath())->toBe('php')
        ->and($codex->getArtisanPath())->toBe('artisan');
});

test('Codex paths and frontmatter are configured', function () {
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $codex = new Codex($strategyFactory);

    expect($codex->mcpConfigPath())->toBe('.codex/mcp.json')
        ->and($codex->guidelinesPath())->toBe('.codex/guidelines.md')
        ->and($codex->frontmatter())->toBeTrue();
});

