<?php

declare(strict_types=1);

use JMac\Testing\Double;
use Laravel\Boost\Mcp\Boost;
use Laravel\Boost\Mcp\Resources\ApplicationInfo;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\ApplicationInfo as ApplicationInfoTool;
use Laravel\Mcp\Response;

it('returns php version, laravel version, packages, and models when tool executes successfully', function (): void {
    $mockData = [
        'php_version' => '8.4.0',
        'laravel_version' => '12.0.0',
        'database_engine' => 'mysql',
        'packages' => [
            ['roster_name' => 'Laravel', 'version' => '12.0.0', 'package_name' => 'laravel/framework'],
        ],
        'models' => ['App\\Models\\User'],
    ];

    $executor = Double::for(ToolExecutor::class);
    $executor->expects('execute')->with(ApplicationInfoTool::class)->returns(Response::json($mockData));
    $this->app->instance(ToolExecutor::class, $executor);

    $response = Boost::resource(ApplicationInfo::class);

    $response
        ->assertOk()
        ->assertSee(['php_version', '8.4.0', 'laravel_version', 'database_engine']);
});

it('propagates tool executor error response directly to the client', function (): void {
    $executor = Double::for(ToolExecutor::class);
    $executor->expects('execute')->with(ApplicationInfoTool::class)->returns(Response::error('Tool execution failed'));
    $this->app->instance(ToolExecutor::class, $executor);

    $response = Boost::resource(ApplicationInfo::class);

    $response->assertHasErrors(['Tool execution failed']);
});

it('returns parsing error when tool response contains malformed json', function (): void {
    $executor = Double::for(ToolExecutor::class);
    $executor->expects('execute')->with(ApplicationInfoTool::class)->returns(Response::text('not-valid-json'));
    $this->app->instance(ToolExecutor::class, $executor);

    $response = Boost::resource(ApplicationInfo::class);

    $response->assertHasErrors(['Error parsing application information']);
});

it('returns a parsing error when tool response is empty string', function (): void {
    $executor = Double::for(ToolExecutor::class);
    $executor->expects('execute')->with(ApplicationInfoTool::class)->returns(Response::text(''));
    $this->app->instance(ToolExecutor::class, $executor);

    $response = Boost::resource(ApplicationInfo::class);

    $response->assertHasErrors(['Error parsing application information']);
});
