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

it('returns default mcp config path', function (): void {
    $agent = new Junie($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('.junie/mcp/mcp.json');
});

it('returns configured mcp config path', function (): void {
    config()->set('boost.agents.junie.mcp_config_path', '../.junie/mcp/mcp.json');

    $agent = new Junie($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe('../.junie/mcp/mcp.json');
});
