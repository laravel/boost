<?php

declare(strict_types=1);

use Laravel\Boost\Install\McpServer;

it('constructs with all properties', function (): void {
    $server = new McpServer(
        name: 'my-server',
        command: 'node',
        args: ['server.js'],
        url: null,
        type: 'stdio',
        env: ['APP_KEY' => 'secret'],
        description: 'My server',
    );

    expect($server->name)->toBe('my-server')
        ->and($server->command)->toBe('node')
        ->and($server->args)->toBe(['server.js'])
        ->and($server->url)->toBeNull()
        ->and($server->type)->toBe('stdio')
        ->and($server->env)->toBe(['APP_KEY' => 'secret'])
        ->and($server->description)->toBe('My server');
});

it('constructs with defaults for optional properties', function(): void {
    $server = new McpServer(name: 'minimal');

    expect($server->command)->toBeNull()
        ->and($server->args)->toBe([])
        ->and($server->url)->toBeNull()
        ->and($server->type)->toBeNull()
        ->and($server->env)->toBe([])
        ->and($server->description)->toBeNull();
});

it('creates from array with all fields', function (): void {
    $server = McpServer::fromArray([
        'name' => 'my-server',
        'command' => 'node',
        'args' => ['server.js'],
        'url' => null,
        'type' => 'stdio',
        'env' => ['APP_KEY' => 'secret'],
        'description' => 'My server',
    ]);

    expect($server->name)->toBe('my-server')
        ->and($server->command)->toBe('node')
        ->and($server->args)->toBe(['server.js'])
        ->and($server->type)->toBe('stdio')
        ->and($server->env)->toBe(['APP_KEY' => 'secret'])
        ->and($server->description)->toBe('My server');
});

it('creates from array for an http server', function (): void {
    $server = McpServer::fromArray([
        'name' => 'remote-server',
        'type' => 'http',
        'url' => 'https://mcp.example.com/mcp',
    ]);

    expect($server->name)->toBe('remote-server')
        ->and($server->url)->toBe('https://mcp.example.com/mcp')
        ->and($server->type)->toBe('http')
        ->and($server->command)->toBeNull();
});

it('throws InvalidArgumentException when name is missing', function (): void {
    expect(fn () => McpServer::fromArray(['command' => 'node']))
        ->toThrow(InvalidArgumentException::class);
});

it('throws InvalidArgumentException when name is empty string', function (): void {
    expect(fn () => McpServer::fromArray(['name' => '']))
        ->toThrow(InvalidArgumentException::class);
});

it('throws InvalidArgumentException when name is not a string', function (): void {
    expect(fn () => McpServer::fromArray(['name' => 123]))
        ->toThrow(InvalidArgumentException::class);
});

it('toConfigArray omits null fields', function (): void {
    $server = new McpServer(name: 'my-server', command: 'node');

    $config = $server->toConfigArray();

    expect($config)->not->toHaveKey('url')
        ->not->toHaveKey('type')
        ->not->toHaveKey('description');
});

it('toConfigArray omits empty array fields', function (): void {
    $server = new McpServer(name: 'my-server', command: 'node', args: [], env: []);

    $config = $server->toConfigArray();

    expect($config)->not->toHaveKey('args')
        ->not->toHaveKey('env');
});

it('toConfigArray includes all non-null non-empty fields', function (): void {
    $server = new McpServer(
        name: 'my-server',
        command: 'node',
        args: ['server.js'],
        env: ['KEY' => 'val'],
        description: 'desc',
    );

    $config = $server->toConfigArray();

    expect($config)->toHaveKey('name', 'my-server')
        ->toHaveKey('command', 'node')
        ->toHaveKey('args', ['server.js'])
        ->toHaveKey('env', ['KEY' => 'val'])
        ->toHaveKey('description', 'desc');
});

it('toConfigArray always includes name', function (): void {
    $server = new McpServer(name: 'only-name');

    expect($server->toConfigArray())->toHaveKey('name', 'only-name');
});
