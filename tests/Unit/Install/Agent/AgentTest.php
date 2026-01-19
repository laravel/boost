<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agent;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Boost\Contracts\SupportMCP;
use Laravel\Boost\Install\Agent\Agent;
use Laravel\Boost\Install\Agent\Copilot;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
});

class TestAgent extends Agent
{
    public function name(): string
    {
        return 'test';
    }

    public function displayName(): string
    {
        return 'Test Environment';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return ['paths' => ['/test/path']];
    }

    public function projectDetectionConfig(): array
    {
        return ['files' => ['test.config']];
    }
}

class TestSupportMCP extends TestAgent implements SupportMCP
{
    public function mcpConfigPath(): string
    {
        return '.test/mcp.json';
    }
}

test('installShellMcp executes command with placeholders replaced', function (): void {
    $agent = Mockery::mock(TestAgent::class)->makePartial();
    $agent->shouldAllowMockingProtectedMethods();

    $agent->shouldReceive('shellMcpCommand')
        ->andReturn('install {key} {command} {args} {env}');

    $agent->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::SHELL);

    $mockResult = Mockery::mock();
    $mockResult->shouldReceive('successful')->andReturn(true);
    $mockResult->shouldReceive('errorOutput')->andReturn('');

    Process::shouldReceive('run')
        ->once()
        ->with(Mockery::on(fn ($command): bool => str_contains((string) $command, 'install test-key test-command "arg1" "arg2"') &&
               str_contains((string) $command, '-e ENV1="value1"') &&
               str_contains((string) $command, '-e ENV2="value2"')))
        ->andReturn($mockResult);

    $result = $agent->installMcp('test-key', 'test-command', ['arg1', 'arg2'], ['env1' => 'value1', 'env2' => 'value2']);

    expect($result)->toBe(true);
});

test('installShellMcp treats "already exists" error as success', function (): void {
    $agent = Mockery::mock(TestAgent::class)->makePartial();
    $agent->shouldAllowMockingProtectedMethods();

    $agent->shouldReceive('shellMcpCommand')
        ->andReturn('install {key}');

    $agent->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::SHELL);

    $mockResult = Mockery::mock();
    $mockResult->shouldReceive('successful')->andReturn(false);
    $mockResult->shouldReceive('errorOutput')->andReturn('Error: already exists');

    Process::shouldReceive('run')
        ->once()
        ->andReturn($mockResult);

    $result = $agent->installMcp('test-key', 'test-command');

    expect($result)->toBe(true);
});

test('installFileMcp creates new config file with correct structure', function (): void {
    $agent = Mockery::mock(TestSupportMCP::class)->makePartial();
    $agent->shouldAllowMockingProtectedMethods();

    $capturedContent = '';
    $expectedContent = <<<'JSON'
{
    "mcpServers": {
        "test-key": {
            "command": "test-command",
            "args": [
                "arg1"
            ],
            "env": {
                "ENV": "value"
            }
        }
    }
}
JSON;

    $agent->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::FILE);

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with('.test');

    File::shouldReceive('exists')
        ->once()
        ->with('.test/mcp.json')
        ->andReturn(false);

    File::shouldReceive('put')
        ->once()
        ->with(Mockery::capture($capturedPath), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $agent->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

    expect($result)->toBe(true)
        ->and($capturedPath)->toBe($agent->mcpConfigPath())
        ->and($capturedContent)->toBe($expectedContent);
});

test('installFileMcp merges with existing config preserving other entries', function (): void {
    $agent = Mockery::mock(TestSupportMCP::class)->makePartial();
    $agent->shouldAllowMockingProtectedMethods();

    $capturedPath = '';
    $capturedContent = '';

    $agent->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::FILE);

    $existingConfig = json_encode(['mcpServers' => ['existing' => ['command' => 'existing-cmd']]]);

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with('.test');

    File::shouldReceive('size')->once()->andReturn(10);

    File::shouldReceive('exists')
        ->once()
        ->with('.test/mcp.json')
        ->andReturn(true);

    File::shouldReceive('get')
        ->once()
        ->with('.test/mcp.json')
        ->andReturn($existingConfig);

    File::shouldReceive('put')
        ->once()
        ->with(Mockery::capture($capturedPath), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $agent->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

    expect($result)->toBe(true)
        ->and($capturedContent)
        ->json()
        ->toMatchArray([
            'mcpServers' => [
                'existing' => [
                    'command' => 'existing-cmd',
                ],
                'test-key' => [
                    'command' => 'test-command',
                    'args' => ['arg1'],
                    'env' => ['ENV' => 'value'],
                ],
            ],
        ]);

});

test('installFileMcp handles JSON5 format with comments and trailing commas', function (): void {
    $copilot = new Copilot($this->strategyFactory);
    $capturedPath = '';
    $capturedContent = '';
    $json5 = fixtureContent('mcp.json5');

    File::shouldReceive('exists')->once()->andReturn(true);
    File::shouldReceive('size')->once()->andReturn(10);
    File::shouldReceive('put')
        ->with(
            Mockery::capture($capturedPath),
            Mockery::capture($capturedContent),
        )
        ->andReturn(true);

    File::shouldReceive('get')
        ->with($copilot->mcpConfigPath())
        ->andReturn($json5)->getMock()->shouldIgnoreMissing();

    $wasWritten = $copilot->installMcp('boost', 'php', ['artisan', 'boost:mcp'], ['SITE_PATH' => '/tmp/']);

    expect($wasWritten)->toBeTrue()
        ->and($capturedPath)->toBe($copilot->mcpConfigPath())
        ->and($capturedContent)->toBe(fixtureContent('mcp-expected.json5'));
});
