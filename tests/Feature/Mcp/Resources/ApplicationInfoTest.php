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

it('integrates with a real tool through a fake executor that bypasses subprocess', function (): void {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.5.0'),
    ]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);
    $this->app->instance(Roster::class, $roster);

    $guidelineAssist = Mockery::mock(GuidelineAssist::class);
    $guidelineAssist->shouldReceive('models')->andReturn([
        'App\\Models\\User' => '/app/Models/User.php',
    ]);
    $this->app->instance(GuidelineAssist::class, $guidelineAssist);

    $fakeExecutor = new class extends ToolExecutor
    {
        public function execute(string $toolClass, array $arguments = []): Response
        {
            $tool = app($toolClass);

            return $tool->handle(new Request($arguments));
        }
    };

    $this->app->instance(ToolExecutor::class, $fakeExecutor);

    $resource = app(ApplicationInfo::class);
    $response = $resource->handle();

    $data = json_decode((string) $response->content(), true);

    expect($data)->toHaveKeys(['php_version', 'laravel_version', 'packages', 'models'])
        ->and($data['packages'])->toHaveCount(1)
        ->sequence(
            fn ($package) => $package->toMatchArray([
                'roster_name' => 'LARAVEL',
                'package_name' => 'laravel/framework',
                'version' => '11.5.0',
            ]),
        )
        ->and($data['models'])->toBe(['App\\Models\\User']);
});
