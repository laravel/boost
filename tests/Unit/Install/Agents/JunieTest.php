<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Install\Agents\Junie;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('httpMcpServerConfig returns npx mcp-remote config', function (): void {
    $agent = new Junie($this->strategyFactory);

    expect($agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'command' => 'npx',
        'args' => ['-y', 'mcp-remote', 'https://nightwatch.laravel.com/mcp'],
    ]);
});

test('projectDetectionConfig only uses .junie dir', function (): void {
    $agent = new Junie($this->strategyFactory);

    expect($agent->projectDetectionConfig())->toBe([
        'paths' => ['.junie'],
    ]);
});

test('detectInProject returns false when only .idea dir exists', function (): void {
    $agent = new Junie(new DetectionStrategyFactory(app()));
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost_junie_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.DIRECTORY_SEPARATOR.'.idea');

    try {
        expect($agent->detectInProject($tempDir))->toBeFalse();
    } finally {
        rmdir($tempDir.DIRECTORY_SEPARATOR.'.idea');
        rmdir($tempDir);
    }
});
