<?php

declare(strict_types=1);

namespace Tests\Unit\Install\Mcp;

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\Mcp\TomlFileWriter;
use League\Flysystem\Filesystem;
use Mockery;

it('creates the new TOML file with the correct structure', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('laravel_boost', [
            'command' => 'php',
            'args' => ['artisan', 'boost:mcp'],
            'cwd' => '/path/to/project',
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.laravel_boost]')
        ->and($capturedContent)->toContain('command = "php"')
        ->and($capturedContent)->toContain('args = ["artisan", "boost:mcp"]')
        ->and($capturedContent)->toContain('cwd = "/path/to/project"');
});

it('creates a new file with base config options', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml', [
        'model' => 'gpt-4',
        'sandbox_mode' => 'workspace-write',
    ]))
        ->configKey('mcp_servers')
        ->addServerConfig('it', ['command' => 'php'])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('model = "gpt-4"')
        ->and($capturedContent)->toContain('sandbox_mode = "workspace-write"')
        ->and($capturedContent)->toContain('[mcp_servers.it]');
});

it('handles nested env table correctly', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('mysql', [
            'command' => 'npx',
            'args' => ['@mysql/mcp-server'],
            'env' => [
                'DB_HOST' => 'localhost',
                'DB_PORT' => '3306',
            ],
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.mysql]')
        ->and($capturedContent)->toContain('command = "npx"')
        ->and($capturedContent)->toContain('[mcp_servers.mysql.env]')
        ->and($capturedContent)->toContain('DB_HOST = "localhost"')
        ->and($capturedContent)->toContain('DB_PORT = "3306"');
});

it('appends to an existing TOML file preserving other servers', function (): void {
    $capturedContent = '';

    mockTomlFileOperations(
        fileExists: true,
        content: fixtureContent('codex-config.toml'),
        capturedContent: $capturedContent
    );

    File::shouldReceive('size')->andReturn(100);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('laravel_boost', [
            'command' => 'php',
            'args' => ['artisan', 'boost:mcp'],
            'cwd' => '/new/path',
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.existing_server]')
        ->and($capturedContent)->toContain('[mcp_servers.laravel_boost]')
        ->and($capturedContent)->toContain('command = "npm"')
        ->and($capturedContent)->toContain('command = "php"');
});

it('updates the existing server by removing an old section and appending a new one', function (): void {
    $capturedContent = '';

    $existingContent = <<<'TOML'
model = "o3"

[mcp_servers.laravel_boost]
command = "php"
args = ["artisan", "boost:mcp"]
cwd = "/old/path"

[mcp_servers.other_server]
command = "npm"
args = ["start"]
TOML;

    mockTomlFileOperations(
        fileExists: true,
        content: $existingContent,
        capturedContent: $capturedContent
    );

    File::shouldReceive('size')->andReturn(strlen($existingContent));

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('laravel_boost', [
            'command' => 'php',
            'args' => ['artisan', 'boost:mcp'],
            'cwd' => '/new/path',
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.other_server]')
        ->and($capturedContent)->toContain('[mcp_servers.laravel_boost]')
        ->and($capturedContent)->toContain('cwd = "/new/path"')
        ->and($capturedContent)->not->toContain('cwd = "/old/path"');
});

it('filters empty values from server config', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('it', [
            'command' => 'php',
            'args' => [],
            'cwd' => '/path',
            'env' => [],
            'empty_string' => '',
            'null_value' => null,
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('command = "php"')
        ->and($capturedContent)->toContain('cwd = "/path"')
        ->and($capturedContent)->not->toContain('args')
        ->and($capturedContent)->not->toContain('[mcp_servers.it.env]')
        ->and($capturedContent)->not->toContain('empty_string')
        ->and($capturedContent)->not->toContain('null_value');
});

it('handles multiple servers in the same file', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('server1', [
            'command' => 'cmd1',
            'cwd' => '/path1',
        ])
        ->addServerConfig('server2', [
            'command' => 'cmd2',
            'cwd' => '/path2',
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.server1]')
        ->and($capturedContent)->toContain('[mcp_servers.server2]')
        ->and($capturedContent)->toContain('command = "cmd1"')
        ->and($capturedContent)->toContain('command = "cmd2"');
});

it('removes server with env subtable when updating', function (): void {
    $capturedContent = '';

    $existingContent = <<<'TOML'
[mcp_servers.laravel_boost]
command = "php"
args = ["artisan", "boost:mcp"]

[mcp_servers.laravel_boost.env]
APP_ENV = "local"

[mcp_servers.other]
command = "npm"
TOML;

    mockTomlFileOperations(
        fileExists: true,
        content: $existingContent,
        capturedContent: $capturedContent
    );

    File::shouldReceive('size')->andReturn(strlen($existingContent));

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('laravel_boost', [
            'command' => 'php',
            'args' => ['artisan', 'boost:mcp'],
            'cwd' => '/updated/path',
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.other]')
        ->and($capturedContent)->toContain('[mcp_servers.laravel_boost]')
        ->and($capturedContent)->toContain('cwd = "/updated/path"')
        ->and($capturedContent)->not->toContain('APP_ENV');
});

it('preserves full codex config with top-level settings', function (): void {
    $capturedContent = '';

    $existingContent = <<<'TOML'
model = "o3"
approval_policy = "on-request"
sandbox_mode = "workspace-write"

[mcp_servers.context7]
command = "npx"
args = ["-y", "@upstash/context7-mcp"]
TOML;

    mockTomlFileOperations(
        fileExists: true,
        content: $existingContent,
        capturedContent: $capturedContent
    );

    File::shouldReceive('size')->andReturn(strlen($existingContent));

    $result = (new TomlFileWriter('/project/.codex/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('laravel_boost', [
            'command' => 'php',
            'args' => ['artisan', 'boost:mcp'],
            'cwd' => '/project',
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('model = "o3"')
        ->and($capturedContent)->toContain('approval_policy = "on-request"')
        ->and($capturedContent)->toContain('sandbox_mode = "workspace-write"')
        ->and($capturedContent)->toContain('[mcp_servers.context7]')
        ->and($capturedContent)->toContain('[mcp_servers.laravel_boost]');
});

it('uses a custom config key', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('servers')
        ->addServerConfig('laravel_boost', [
            'command' => 'php',
            'args' => ['artisan', 'boost:mcp'],
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[servers.laravel_boost]')
        ->and($capturedContent)->not->toContain('[mcp_servers');
});

it('handles special characters in string values', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('it', [
            'command' => 'node',
            'args' => ['--eval', 'console.log("hello")'],
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('args = ["--eval", "console.log(\\"hello\\")"]');
});

it('handles paths with spaces', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/Users/My User/My Projects/app/.codex/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('laravel_boost', [
            'command' => 'php',
            'args' => ['artisan', 'boost:mcp'],
            'cwd' => '/Users/My User/My Projects/app',
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('cwd = "/Users/My User/My Projects/app"');
});

it('handles windows-style paths with backslashes', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('laravel_boost', [
            'command' => 'php',
            'args' => ['artisan', 'boost:mcp'],
            'cwd' => 'C:\\Users\\Developer\\Projects\\my-app',
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('cwd = "C:\\\\Users\\\\Developer\\\\Projects\\\\my-app"');
});

it('repeated updates do not accumulate blank lines', function (): void {
    $capturedContent = '';

    $existingContent = <<<'TOML'
model = "o3"

[mcp_servers.laravel_boost]
command = "php"
args = ["artisan", "boost:mcp"]
cwd = "/old/path"
TOML;

    mockTomlFileOperations(
        fileExists: true,
        content: $existingContent,
        capturedContent: $capturedContent
    );

    File::shouldReceive('size')->andReturn(strlen($existingContent));

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('laravel_boost', [
            'command' => 'php',
            'args' => ['artisan', 'boost:mcp'],
            'cwd' => '/new/path',
        ])
        ->save();

    $eol = PHP_EOL;
    expect($result)->toBeTrue()
        ->and($capturedContent)->not->toMatch('/(\r?\n){3,}/')
        ->and($capturedContent)->toContain("model = \"o3\"{$eol}{$eol}[mcp_servers.laravel_boost]");
});

it('formats boolean values correctly', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('it', [
            'command' => 'php',
            'enabled' => true,
            'disabled' => false,
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('enabled = true')
        ->and($capturedContent)->toContain('disabled = false');
});

it('formats numeric values correctly', function (): void {
    $capturedContent = '';
    mockTomlFileOperations(capturedContent: $capturedContent);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('it', [
            'command' => 'php',
            'timeout' => 30,
            'retries' => 0,
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('timeout = 30')
        ->and($capturedContent)->toContain('retries = 0');
});

it('handles server names with regex special characters', function (): void {
    $capturedContent = '';

    $existingContent = <<<'TOML'
[mcp_servers.my-server.v2]
command = "old"

[mcp_servers.other]
command = "npm"
TOML;

    mockTomlFileOperations(
        fileExists: true,
        content: $existingContent,
        capturedContent: $capturedContent
    );

    File::shouldReceive('size')->andReturn(strlen($existingContent));

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('my-server.v2', [
            'command' => 'new',
        ])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.other]')
        ->and($capturedContent)->toContain('[mcp_servers.my-server.v2]')
        ->and($capturedContent)->toContain('command = "new"')
        ->and($capturedContent)->not->toContain('command = "old"');
});

it('treats an empty file as a new file', function (): void {
    $capturedContent = '';

    mockTomlFileOperations(
        fileExists: true,
        capturedContent: $capturedContent
    );

    File::shouldReceive('size')->andReturn(0);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('it', ['command' => 'php'])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.it]')
        ->and($capturedContent)->toContain('command = "php"');
});

it('treats a file with only whitespace as a new file', function (): void {
    $capturedContent = '';

    mockTomlFileOperations(
        fileExists: true,
        content: '  ',
        capturedContent: $capturedContent
    );

    File::shouldReceive('size')->andReturn(2);

    $result = (new TomlFileWriter('/path/to/config.toml'))
        ->configKey('mcp_servers')
        ->addServerConfig('it', ['command' => 'php'])
        ->save();

    expect($result)->toBeTrue()
        ->and($capturedContent)->toContain('[mcp_servers.it]')
        ->and($capturedContent)->toContain('command = "php"');
});

function mockTomlFileOperations(
    bool $fileExists = false,
    string $content = '',
    bool $writeSuccess = true,
    ?string &$capturedPath = null,
    ?string &$capturedContent = null
): void {
    File::swap(Mockery::mock(Filesystem::class));

    File::shouldReceive('ensureDirectoryExists')->once();
    File::shouldReceive('exists')->andReturn($fileExists);

    if ($fileExists) {
        File::shouldReceive('get')->andReturn($content);
    }

    if (! is_null($capturedPath) || ! is_null($capturedContent)) {
        File::shouldReceive('put')
            ->once()
            ->with(
                Mockery::capture($capturedPath),
                Mockery::capture($capturedContent)
            )
            ->andReturn($writeSuccess);
    } else {
        File::shouldReceive('put')->once()->andReturn($writeSuccess);
    }
}
