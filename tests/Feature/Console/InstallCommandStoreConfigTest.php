<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Support\Config;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->tempBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-install-store-config-test-'.uniqid();

    File::makeDirectory($this->tempBasePath, 0755, true);
    $this->app->setBasePath($this->tempBasePath);

    file_put_contents($this->tempBasePath.'/composer.lock', json_encode([
        'packages' => [
            ['name' => 'laravel/framework', 'version' => 'v11.0.0'],
        ],
        'packages-dev' => [],
    ]));

    config(['boost.agents.claude_code.mcp_config_path' => $this->tempBasePath.'/.mcp.json']);
});

afterEach(function (): void {
    (new Config)->flush();
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->tempBasePath);
});

function mockAgentsDetector(array $systemInstalled): void
{
    $agents = app(AgentsDetector::class)->getAgents();

    $mock = Mockery::mock(AgentsDetector::class);
    $mock->shouldReceive('getAgents')->andReturn($agents);
    $mock->shouldReceive('discoverSystemInstalledAgents')->andReturn($systemInstalled);
    $mock->shouldReceive('discoverProjectInstalledAgents')->andReturn([]);

    app()->instance(AgentsDetector::class, $mock);
}

it('stores the selected agents in boost.json when installing with an explicit flag', function (): void {
    mockAgentsDetector(['claude_code']);

    $this->artisan('boost:install', ['--mcp' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect((new Config)->getAgents())->toBe(['claude_code'])
        ->and(file_exists($this->tempBasePath.'/.mcp.json'))->toBeTrue();
});

it('keeps the existing agents entry when re-running with an explicit flag', function (): void {
    (new Config)->setAgents(['claude_code']);

    mockAgentsDetector([]);

    $this->artisan('boost:install', ['--mcp' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect((new Config)->getAgents())->toBe(['claude_code']);
});

it('does not replace the agents entry with an empty list when no agents match', function (): void {
    (new Config)->setAgents(['gemini']);

    mockAgentsDetector([]);

    $this->artisan('boost:install', ['--mcp' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect((new Config)->getAgents())->toBe(['gemini']);
});
