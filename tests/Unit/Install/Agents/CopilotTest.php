<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use JMac\Testing\Double;
use Laravel\Boost\Install\Agents\Copilot;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

beforeEach(function (): void {
    $this->strategyFactory = Double::for(DetectionStrategyFactory::class);
});

test('httpMcpServerConfig returns default http config', function (): void {
    $agent = new Copilot($this->strategyFactory);

    expect($agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'type' => 'http',
        'url' => 'https://nightwatch.laravel.com/mcp',
    ]);
});
