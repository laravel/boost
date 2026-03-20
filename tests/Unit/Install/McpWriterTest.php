<?php

declare(strict_types=1);

use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Install\McpWriter;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;

it('installs boost mcp successfully without sail', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->once()
        ->andReturn('php');
    $agent->shouldReceive('getArtisanPath')
        ->once()
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);

    $writer = new McpWriter($agent);
    $result = $writer->write();

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('installs boost mcp with sail', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'vendor/bin/sail', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);

    $sail = Mockery::mock(Sail::class);
    $sail->shouldReceive('buildMcpCommand')
        ->with('laravel-boost')
        ->once()
        ->andReturn([
            'key' => 'laravel-boost',
            'command' => 'vendor/bin/sail',
            'args' => ['artisan', 'boost:mcp'],
        ]);

    $writer = new McpWriter($agent);
    $result = $writer->write($sail);

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('throws exception when boost mcp installation returns false', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->andReturn('php');
    $agent->shouldReceive('getArtisanPath')
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(false);

    $writer = new McpWriter($agent);

    expect(fn (): int => $writer->write())
        ->toThrow(RuntimeException::class, 'Failed to install Boost MCP: could not write configuration');
});

it('throws exception when boost mcp installation throws exception', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->andReturn('php');
    $agent->shouldReceive('getArtisanPath')
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andThrow(new RuntimeException('Permission denied'));

    $writer = new McpWriter($agent);

    expect(fn (): int => $writer->write())
        ->toThrow(RuntimeException::class, 'Permission denied');
});

it('installs nightwatch mcp when nightwatch is provided', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->once()
        ->andReturn('php');
    $agent->shouldReceive('getArtisanPath')
        ->once()
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installHttpMcp')
        ->with('nightwatch', 'https://nightwatch.laravel.com/mcp')
        ->once()
        ->andReturn(true);

    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('mcpUrl')
        ->once()
        ->andReturn('https://nightwatch.laravel.com/mcp');

    $writer = new McpWriter($agent);
    $result = $writer->write(null, $nightwatch);

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('throws exception when nightwatch mcp installation returns false', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->once()
        ->andReturn('php');
    $agent->shouldReceive('getArtisanPath')
        ->once()
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installHttpMcp')
        ->with('nightwatch', 'https://nightwatch.laravel.com/mcp')
        ->once()
        ->andReturn(false);

    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('mcpUrl')
        ->once()
        ->andReturn('https://nightwatch.laravel.com/mcp');

    $writer = new McpWriter($agent);

    expect(fn (): int => $writer->write(null, $nightwatch))
        ->toThrow(RuntimeException::class, 'Failed to install Nightwatch MCP: could not write configuration');
});

it('does not install nightwatch mcp when nightwatch is null', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->once()
        ->andReturn('php');
    $agent->shouldReceive('getArtisanPath')
        ->once()
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldNotReceive('installHttpMcp');

    $writer = new McpWriter($agent);
    $result = $writer->write();

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('installs with both sail and nightwatch', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'vendor/bin/sail', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installHttpMcp')
        ->with('nightwatch', 'https://nightwatch.laravel.com/mcp')
        ->once()
        ->andReturn(true);

    $sail = Mockery::mock(Sail::class);
    $sail->shouldReceive('buildMcpCommand')
        ->with('laravel-boost')
        ->once()
        ->andReturn([
            'key' => 'laravel-boost',
            'command' => 'vendor/bin/sail',
            'args' => ['artisan', 'boost:mcp'],
        ]);

    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('mcpUrl')
        ->once()
        ->andReturn('https://nightwatch.laravel.com/mcp');

    $writer = new McpWriter($agent);
    $result = $writer->write($sail, $nightwatch);

    expect($result)->toBe(McpWriter::SUCCESS);
});

// Third-party server tests

it('installs a command-based third-party server via installMcp', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')->andReturn('php');
    $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installMcp')
        ->with('acme-server', 'node', ['server.js'], ['KEY' => 'val'])
        ->once()
        ->andReturn(true);

    $server = new \Laravel\Boost\Install\McpServer(
        name: 'acme-server',
        command: 'node',
        args: ['server.js'],
        env: ['KEY' => 'val'],
    );

    $writer = new McpWriter($agent);
    $result = $writer->write(null, null, collect([$server]));

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('installs an http third-party server via installHttpMcp', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')->andReturn('php');
    $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installHttpMcp')
        ->with('acme-remote', 'https://mcp.acme.com/mcp')
        ->once()
        ->andReturn(true);

    $server = new \Laravel\Boost\Install\McpServer(
        name: 'acme-remote',
        url: 'https://mcp.acme.com/mcp',
    );

    $writer = new McpWriter($agent);
    $result = $writer->write(null, null, collect([$server]));

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('skips third-party server that conflicts with laravel-boost key', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')->andReturn('php');
    $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldNotReceive('installHttpMcp');
    // The third-party 'laravel-boost' server should be skipped (conflict), so installMcp
    // should only be called once (for the first-party boost install above).

    $server = new \Laravel\Boost\Install\McpServer(
        name: 'laravel-boost',
        url: 'https://evil.com/mcp',
    );

    $writer = new McpWriter($agent);
    $result = $writer->write(null, null, collect([$server]));

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('skips third-party server that conflicts with nightwatch key', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')->andReturn('php');
    $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldNotReceive('installHttpMcp');

    $server = new \Laravel\Boost\Install\McpServer(
        name: 'nightwatch',
        url: 'https://evil.com/mcp',
    );

    $writer = new McpWriter($agent);
    $result = $writer->write(null, null, collect([$server]));

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('throws RuntimeException when third-party command server install fails', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')->andReturn('php');
    $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installMcp')
        ->with('failing-server', 'node', [], [])
        ->once()
        ->andReturn(false);

    $server = new \Laravel\Boost\Install\McpServer(name: 'failing-server', command: 'node');

    $writer = new McpWriter($agent);

    expect(fn () => $writer->write(null, null, collect([$server])))
        ->toThrow(RuntimeException::class, 'failing-server');
});

it('throws RuntimeException when third-party http server install fails', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')->andReturn('php');
    $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installHttpMcp')
        ->with('failing-http', 'https://mcp.example.com')
        ->once()
        ->andReturn(false);

    $server = new \Laravel\Boost\Install\McpServer(name: 'failing-http', url: 'https://mcp.example.com');

    $writer = new McpWriter($agent);

    expect(fn () => $writer->write(null, null, collect([$server])))
        ->toThrow(RuntimeException::class, 'failing-http');
});

it('does not install third-party servers when collection is null', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')->andReturn('php');
    $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldNotReceive('installHttpMcp');

    $writer = new McpWriter($agent);
    $result = $writer->write(null, null, null);

    expect($result)->toBe(McpWriter::SUCCESS);
});
