<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Agents;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\Contracts\DetectionStrategy;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function (): void {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $this->strategy = Mockery::mock(DetectionStrategy::class);
});

// Create a concrete test implementation for testing abstract methods
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

    public function testNormalizeCommand(string $command, array $args = []): array
    {
        return $this->normalizeCommand($command, $args);
    }
}

class TestAgentGuidelines extends TestAgent implements SupportsGuidelines
{
    public function guidelinesPath(): string
    {
        return 'test-guidelines.md';
    }
}

class TestSupportsMcp extends TestAgent implements SupportsMcp
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
    $projectConfig = ['files' => ['test.config']];
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

    $environment = new TestAgent($this->strategyFactory);
    $result = $environment->detectInProject($basePath);

    expect($result)->toBe(false);
});

test('installMcp uses Shell strategy when configured', function (): void {
    $environment = Mockery::mock(TestAgent::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $environment->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::SHELL);

    $environment->shouldReceive('installShellMcp')
        ->once()
        ->with('test-key', 'test-command', ['arg1'], ['ENV' => 'value'])
        ->andReturn(true);

    $result = $environment->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

    expect($result)->toBe(true);
});

test('installMcp uses File strategy when configured', function (): void {
    $environment = Mockery::mock(TestAgent::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $environment->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::FILE);

    $environment->shouldReceive('installFileMcp')
        ->once()
        ->with('test-key', 'test-command', ['arg1'], ['ENV' => 'value'])
        ->andReturn(true);

    $result = $environment->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

    expect($result)->toBe(true);
});

test('installMcp returns false for None strategy', function (): void {
    $environment = Mockery::mock(TestAgent::class)->makePartial();

    $environment->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::NONE);

    $result = $environment->installMcp('test-key', 'test-command');

    expect($result)->toBe(false);
});

test('installShellMcp returns false when shellMcpCommand is null', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->installMcp('test-key', 'test-command');

    expect($result)->toBe(false);
});

test('installShellMcp executes command with placeholders replaced', function (): void {
    $environment = Mockery::mock(TestAgent::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $environment->shouldReceive('shellMcpCommand')
        ->andReturn('install {key} {command} {args} {env}');

    $environment->shouldReceive('mcpInstallationStrategy')
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

    $result = $environment->installMcp('test-key', 'test-command', ['arg1', 'arg2'], ['env1' => 'value1', 'env2' => 'value2']);

    expect($result)->toBe(true);
});

test('installShellMcp returns true when process fails but has already exists error', function (): void {
    $environment = Mockery::mock(TestAgent::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $environment->shouldReceive('shellMcpCommand')
        ->andReturn('install {key}');

    $environment->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::SHELL);

    $mockResult = Mockery::mock();
    $mockResult->shouldReceive('successful')->andReturn(false);
    $mockResult->shouldReceive('errorOutput')->andReturn('Error: already exists');

    Process::shouldReceive('run')
        ->once()
        ->andReturn($mockResult);

    $result = $environment->installMcp('test-key', 'test-command');

    expect($result)->toBe(true);
});

test('installFileMcp returns false when mcpConfigPath is null', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->installMcp('test-key', 'test-command');

    expect($result)->toBe(false);
});

test('installFileMcp creates new config file when none exists', function (): void {
    $environment = Mockery::mock(TestSupportsMcp::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

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

    $environment->shouldReceive('mcpInstallationStrategy')
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

    $result = $environment->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

    expect($result)->toBe(true)
        ->and($capturedPath)->toBe($environment->mcpConfigPath())
        ->and($capturedContent)->toBe($expectedContent);
});

test('installFileMcp updates existing config file', function (): void {
    $environment = Mockery::mock(TestSupportsMcp::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $capturedPath = '';
    $capturedContent = '';

    $environment->shouldReceive('mcpInstallationStrategy')
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

    $result = $environment->installMcp('test-key', 'test-command', ['arg1'], ['ENV' => 'value']);

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

test('getPhpPath uses absolute paths when forceAbsolutePath is true and config is empty', function (): void {
    config(['boost.executable_paths.php' => null]);
    $environment = new TestAgent($this->strategyFactory);
    expect($environment->getPhpPath(true))->toBe(PHP_BINARY);
});

test('getPhpPath maintains default behavior when forceAbsolutePath is false and config is empty', function (): void {
    config(['boost.executable_paths.php' => null]);
    $environment = new TestAgent($this->strategyFactory);
    expect($environment->getPhpPath(false))->toBe('php');
});

test('getPhpPath uses configured default_php_bin from config', function (): void {
    config(['boost.executable_paths.php' => '/usr/local/bin/php8.3']);

    $environment = new TestAgent($this->strategyFactory);
    expect($environment->getPhpPath(false))->toBe('/usr/local/bin/php8.3');
});

test('getPhpPath returns php when config is set to php', function (): void {
    config(['boost.executable_paths.php' => 'php']);

    $environment = new TestAgent($this->strategyFactory);
    expect($environment->getPhpPath(false))->toBe('php');
});

test('getPhpPath uses config even when forceAbsolutePath is true', function (): void {
    config(['boost.executable_paths.php' => '/usr/local/bin/php8.3']);

    $environment = new TestAgent($this->strategyFactory);
    expect($environment->getPhpPath(true))->toBe('/usr/local/bin/php8.3');
});

test('getPhpPath uses PHP_BINARY when forceAbsolutePath is true and config is empty', function (): void {
    config(['boost.executable_paths.php' => null]);

    $environment = new TestAgent($this->strategyFactory);
    expect($environment->getPhpPath(true))->toBe(PHP_BINARY);
});

test('preserves simple commands without normalisation', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->testNormalizeCommand('php', ['artisan', 'boost:mcp']);

    expect($result)->toBe([
        'command' => 'php',
        'args' => ['artisan', 'boost:mcp'],
    ]);
});

test('splits valet php into command and arguments', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->testNormalizeCommand('valet php', ['artisan', 'boost:mcp']);

    expect($result)->toBe([
        'command' => 'valet',
        'args' => ['php', 'artisan', 'boost:mcp'],
    ]);
});

test('splits herd php into command and arguments', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->testNormalizeCommand('herd php', ['artisan', 'boost:mcp']);

    expect($result)->toBe([
        'command' => 'herd',
        'args' => ['php', 'artisan', 'boost:mcp'],
    ]);
});

test('splits docker exec commands into parts', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->testNormalizeCommand('docker exec container php', ['artisan']);

    expect($result)->toBe([
        'command' => 'docker',
        'args' => ['exec', 'container', 'php', 'artisan'],
    ]);
});

test('splits commands even without additional arguments', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->testNormalizeCommand('valet php');

    expect($result)->toBe([
        'command' => 'valet',
        'args' => ['php'],
    ]);
});

test('preserves single commands without arguments', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->testNormalizeCommand('php');

    expect($result)->toBe([
        'command' => 'php',
        'args' => [],
    ]);
});

test('shell installation handles valet php commands', function (): void {
    $environment = Mockery::mock(TestAgent::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $environment->shouldReceive('shellMcpCommand')
        ->andReturn('install {key} {command} {args}');

    $environment->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::SHELL);

    $mockResult = Mockery::mock();
    $mockResult->shouldReceive('successful')->andReturn(true);
    $mockResult->shouldReceive('errorOutput')->andReturn('');

    Process::shouldReceive('run')
        ->once()
        ->with(Mockery::on(fn ($command): bool => str_contains((string) $command, 'install test-key valet') &&
               str_contains((string) $command, '"php"') &&
               str_contains((string) $command, '"artisan"')))
        ->andReturn($mockResult);

    $result = $environment->installMcp('test-key', 'valet php', ['artisan', 'boost:mcp']);

    expect($result)->toBe(true);
});

test('shell installation handles herd php commands', function (): void {
    $environment = Mockery::mock(TestAgent::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $environment->shouldReceive('shellMcpCommand')
        ->andReturn('install {key} {command} {args}');

    $environment->shouldReceive('mcpInstallationStrategy')
        ->andReturn(McpInstallationStrategy::SHELL);

    $mockResult = Mockery::mock();
    $mockResult->shouldReceive('successful')->andReturn(true);
    $mockResult->shouldReceive('errorOutput')->andReturn('');

    Process::shouldReceive('run')
        ->once()
        ->with(Mockery::on(fn ($command): bool => str_contains((string) $command, 'install test-key herd') &&
               str_contains((string) $command, '"php"') &&
               str_contains((string) $command, '"artisan"')))
        ->andReturn($mockResult);

    $result = $environment->installMcp('test-key', 'herd php', ['artisan', 'boost:mcp']);

    expect($result)->toBe(true);
});

test('file installation handles valet php commands', function (): void {
    $environment = Mockery::mock(TestSupportsMcp::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $capturedContent = '';

    $environment->shouldReceive('mcpInstallationStrategy')
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
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $environment->installMcp('test-key', 'valet php', ['artisan', 'boost:mcp']);

    expect($result)->toBe(true)
        ->and($capturedContent)
        ->json()
        ->toMatchArray([
            'mcpServers' => [
                'test-key' => [
                    'command' => 'valet',
                    'args' => ['php', 'artisan', 'boost:mcp'],
                ],
            ],
        ]);
});

test('file installation handles herd php commands', function (): void {
    $environment = Mockery::mock(TestSupportsMcp::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $capturedContent = '';

    $environment->shouldReceive('mcpInstallationStrategy')
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
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $environment->installMcp('test-key', 'herd php', ['artisan', 'boost:mcp']);

    expect($result)->toBe(true)
        ->and($capturedContent)
        ->json()
        ->toMatchArray([
            'mcpServers' => [
                'test-key' => [
                    'command' => 'herd',
                    'args' => ['php', 'artisan', 'boost:mcp'],
                ],
            ],
        ]);
});

test('file installation handles docker exec commands', function (): void {
    $environment = Mockery::mock(TestSupportsMcp::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $capturedContent = '';

    $environment->shouldReceive('mcpInstallationStrategy')
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
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $environment->installMcp('test-key', 'docker exec container php', ['artisan', 'boost:mcp']);

    expect($result)->toBe(true)
        ->and($capturedContent)
        ->json()
        ->toMatchArray([
            'mcpServers' => [
                'test-key' => [
                    'command' => 'docker',
                    'args' => ['exec', 'container', 'php', 'artisan', 'boost:mcp'],
                ],
            ],
        ]);
});

test('preserves absolute unix paths with spaces without splitting', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->testNormalizeCommand(
        '/Users/dev/Library/Application Support/Herd/bin/php83',
        ['artisan', 'boost:mcp']
    );

    expect($result)->toBe([
        'command' => '/Users/dev/Library/Application Support/Herd/bin/php83',
        'args' => ['artisan', 'boost:mcp'],
    ]);
});

test('preserves absolute unix paths without spaces without splitting', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->testNormalizeCommand(
        '/usr/local/bin/php',
        ['artisan', 'boost:mcp']
    );

    expect($result)->toBe([
        'command' => '/usr/local/bin/php',
        'args' => ['artisan', 'boost:mcp'],
    ]);
});

test('preserves absolute windows paths with spaces without splitting', function (): void {
    $environment = new TestAgent($this->strategyFactory);

    $result = $environment->testNormalizeCommand(
        'C:\\Program Files\\PHP\\php.exe',
        ['artisan', 'boost:mcp']
    );

    expect($result)->toBe([
        'command' => 'C:\\Program Files\\PHP\\php.exe',
        'args' => ['artisan', 'boost:mcp'],
    ]);
});

test('file installation handles absolute paths with spaces correctly', function (): void {
    $environment = Mockery::mock(TestSupportsMcp::class)->makePartial();
    $environment->shouldAllowMockingProtectedMethods();

    $capturedContent = '';

    $environment->shouldReceive('mcpInstallationStrategy')
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
        ->with(Mockery::any(), Mockery::capture($capturedContent))
        ->andReturn(true);

    $result = $environment->installMcp(
        'test-key',
        '/Users/dev/Library/Application Support/Herd/bin/php83',
        ['artisan', 'boost:mcp']
    );

    expect($result)->toBe(true)
        ->and($capturedContent)
        ->json()
        ->toMatchArray([
            'mcpServers' => [
                'test-key' => [
                    'command' => '/Users/dev/Library/Application Support/Herd/bin/php83',
                    'args' => ['artisan', 'boost:mcp'],
                ],
            ],
        ]);
});
