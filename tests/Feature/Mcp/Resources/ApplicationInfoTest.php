<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Mcp\Boost;
use Laravel\Boost\Mcp\Resources\ApplicationInfo;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\ApplicationInfo as ApplicationInfoTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;
use Mockery\MockInterface;

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

    $this->mock(ToolExecutor::class, function (MockInterface $mock) use ($mockData): void {
        $mock->shouldReceive('execute')
            ->once()
            ->with(ApplicationInfoTool::class)
            ->andReturn(Response::json($mockData));
    });

    $response = Boost::resource(ApplicationInfo::class);

    $response
        ->assertOk()
        ->assertSee(['php_version', '8.4.0', 'laravel_version', 'database_engine']);
});

it('propagates tool executor error response directly to the client', function (): void {
    $this->mock(ToolExecutor::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->with(ApplicationInfoTool::class)
            ->andReturn(Response::error('Tool execution failed'));
    });

    $response = Boost::resource(ApplicationInfo::class);

    $response->assertHasErrors(['Tool execution failed']);
});

it('returns parsing error when tool response contains malformed json', function (): void {
    $this->mock(ToolExecutor::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->with(ApplicationInfoTool::class)
            ->andReturn(Response::text('not-valid-json'));
    });

    $response = Boost::resource(ApplicationInfo::class);

    $response->assertHasErrors(['Error parsing application information']);
});

it('returns a parsing error when tool response is empty string', function (): void {
    $this->mock(ToolExecutor::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->with(ApplicationInfoTool::class)
            ->andReturn(Response::text(''));
    });

    $response = Boost::resource(ApplicationInfo::class);

    $response->assertHasErrors(['Error parsing application information']);
});
