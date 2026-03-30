<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Laravel\Boost\Install\Agents\OpenCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

test('httpMcpServerConfig returns remote type config', function (): void {
    $agent = new OpenCode($this->strategyFactory);

    $config = $agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp');

    expect($config)->toMatchArray([
        'type' => 'remote',
        'enabled' => true,
        'url' => 'https://nightwatch.laravel.com/mcp',
    ]);
    expect(json_encode($config['oauth']))->toBe('{}');
});
