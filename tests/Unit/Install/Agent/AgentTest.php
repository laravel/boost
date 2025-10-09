<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agent;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Boost\Contracts\Guideline;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\Agents\VSCode;
use Laravel\Boost\Install\Contracts\DetectionStrategy;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $this->strategy = Mockery::mock(DetectionStrategy::class);
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

class TestGuideline extends TestAgent implements Guideline
{
    public function guidelinesPath(): string
    {
        return 'test-guidelines.md';
    }
}

class TestMcpClient extends TestAgent implements McpClient
{
    public function mcpConfigPath(): string
    {
        return '.test/mcp.json';
    }
}

test('detectOnSystem delegates to strategy factory and detection strategy', function (): void {
    $platform = Platform::Darwin;
    $config = ['paths' => ['/test/path']];

    $this->strategyFactory
        ->shouldReceive('makeFromConfig')
        ->once()
        ->with($config)
        ->andReturn($this->strategy);

    $this->strategy
        ->shouldReceive('detect')
        ->once()
        ->with($config, $platform)
        ->andReturn(true);

    $environment = new TestAgent($this->strategyFactory);
    $result = $environment->detectOnSystem($platform);

    expect($result)->toBe(true);
});

test('detectInProject merges config with basePath and delegates to strategy', function (): void {
    $basePath = '/project/path';
    $mergedConfig = ['files' => ['test.config'], 'basePath' => $basePath];

    $this->strategyFactory
        ->shouldReceive('makeFromConfig')
        ->once()
        ->with($mergedConfig)
        ->andReturn($this->strategy);

    $this->strategy
        ->shouldReceive('detect')
        ->once()
        ->with($mergedConfig)
        ->andReturn(false);

    $agent = new TestAgent($this->strategyFactory);
    $result = $agent->detectInProject($basePath);

    expect($result)->toBe(false);
});

test('guidelineProviderName returns displayName by default', function (): void {
    $agent = new TestAgent($this->strategyFactory);

    expect($agent->guidelineProviderName())->toBe('Test Environment');
});

test('mcpClientName returns displayName by default', function (): void {
    $agent = new TestAgent($this->strategyFactory);

    expect($agent->mcpClientName())->toBe('Test Environment');
});

test('mcpInstallationStrategy returns File by default', function (): void {
    $agent = new TestAgent($this->strategyFactory);

    expect($agent->mcpInstallationStrategy())->toBe(McpInstallationStrategy::FILE);
});

test('shellMcpCommand returns null by default', function (): void {
    $agent = new TestAgent($this->strategyFactory);

    expect($agent->shellMcpCommand())->toBe(null);
});

test('mcpConfigPath returns null by default', function (): void {
    $agent = new TestAgent($this->strategyFactory);

    expect($agent->mcpConfigPath())->toBe(null);
});

test('frontmatter returns false by default', function (): void {
    $agent = new TestAgent($this->strategyFactory);

    expect($agent->frontmatter())->toBe(false);
});

test('mcpConfigKey returns mcpServers by default', function (): void {
    $agent = new TestAgent($this->strategyFactory);

    expect($agent->mcpConfigKey())->toBe('mcpServers');
});

test('installMcp uses Shell strategy when configured', function (): void {
    $agent = Mockery::mock(TestAgent::class)->makePartial();
    $agent->shouldAllowMockingProtectedMethods();

    $agent->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::SHELL);

    $agent->shouldReceive('installShellMcp')
        ->once()
        ->with('test-key', 'test-command', ['arg1'], ['ENV' => 'value'])
        ->andReturn(true);

    $result = $agent->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

    expect($result)->toBe(true);
});

test('installMcp uses File strategy when configured', function (): void {
    $agent = Mockery::mock(TestAgent::class)->makePartial();
    $agent->shouldAllowMockingProtectedMethods();

    $agent->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::FILE);

    $agent->shouldReceive('installFileMcp')
        ->once()
        ->with('test-key', 'test-command', ['arg1'], ['ENV' => 'value'])
        ->andReturn(true);

    $result = $agent->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

    expect($result)->toBe(true);
});

test('installMcp returns false for None strategy', function (): void {
    $agent = Mockery::mock(TestAgent::class)->makePartial();

    $agent->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::NONE);

    $result = $agent->installMcp('test-key', 'test-command');

    expect($result)->toBe(false);
});

test('installShellMcp returns false when shellMcpCommand is null', function (): void {
    $agent = new TestAgent($this->strategyFactory);

    $result = $agent->installMcp('test-key', 'test-command');

    expect($result)->toBe(false);
});

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

    $result = $agent->installMcp('test-key', 'test-command', ['arg1', 'arg2'], ['ENV1' => 'value1', 'ENV2' => 'value2']);

    expect($result)->toBe(true);
});

test('installShellMcp returns true when process fails but has already exists error', function (): void {
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

test('installFileMcp returns false when mcpConfigPath is null', function (): void {
    $agent = new TestAgent($this->strategyFactory);

    $result = $agent->installMcp('test-key', 'test-command');

    expect($result)->toBe(false);
});

test('installFileMcp creates new config file when none exists', function (): void {
    $mcpClient = Mockery::mock(TestMcpClient::class)->makePartial();
    $mcpClient->shouldAllowMockingProtectedMethods();

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

    $mcpClient->shouldReceive('mcpInstallationStrategy')
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

    $result = $mcpClient->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

    expect($result)->toBe(true)
        ->and($capturedPath)->toBe($mcpClient->mcpConfigPath())
        ->and($capturedContent)->toBe($expectedContent);
});

test('installFileMcp updates existing config file', function (): void {
    $mcpClient = Mockery::mock(TestMcpClient::class)->makePartial();
    $mcpClient->shouldAllowMockingProtectedMethods();

    $capturedPath = '';
    $capturedContent = '';

    $mcpClient->shouldReceive('mcpInstallationStrategy')
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

    $result = $mcpClient->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

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

test('installFileMcp works with existing config file using JSON 5', function (): void {
    $vscode = new VSCode($this->strategyFactory);
    $capturedPath = '';
    $capturedContent = '';
    $json5 = fixture('mcp.json5');

    File::shouldReceive('exists')->once()->andReturn(true);
    File::shouldReceive('size')->once()->andReturn(10);
    File::shouldReceive('put')
        ->with(
            Mockery::capture($capturedPath),
            Mockery::capture($capturedContent),
        )
        ->andReturn(true);

    File::shouldReceive('get')
        ->with($vscode->mcpConfigPath())
        ->andReturn($json5)->getMock()->shouldIgnoreMissing();

    $wasWritten = $vscode->installMcp('boost', 'php', ['artisan', 'boost:mcp'], ['SITE_PATH' => '/tmp/']);

    expect($wasWritten)->toBeTrue()
        ->and($capturedPath)->toBe($vscode->mcpConfigPath())
        ->and($capturedContent)->toBe(fixture('mcp-expected.json5'));
});

test('getPhpPath uses absolute paths when forceAbsolutePath is true', function (): void {
    $agent = new TestAgent($this->strategyFactory);
    expect($agent->getPhpPath(true))->toBe(PHP_BINARY);
});

test('getPhpPath maintains the default behavior when forceAbsolutePath is false', function (): void {
    $agent = new TestAgent($this->strategyFactory);
    expect($agent->getPhpPath(false))->toBe('php');
});
