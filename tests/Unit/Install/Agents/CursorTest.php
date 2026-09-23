<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use JMac\Testing\Double;
use Laravel\Boost\Install\Agents\Cursor;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

beforeEach(function (): void {
    $this->strategyFactory = Double::for(DetectionStrategyFactory::class);
});

test('httpMcpServerConfig returns npx mcp-remote config', function (): void {
    $agent = new Cursor($this->strategyFactory);

    expect($agent->httpMcpServerConfig('https://nightwatch.laravel.com/mcp'))->toBe([
        'command' => 'npx',
        'args' => ['-y', 'mcp-remote', 'https://nightwatch.laravel.com/mcp'],
    ]);
});
