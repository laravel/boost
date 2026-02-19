<?php

declare(strict_types=1);

use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Install\McpWriter;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Install\Svelte;

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

it('installs herd mcp when herd is provided', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->twice()
        ->andReturn('php', '/usr/bin/php');
    $agent->shouldReceive('getArtisanPath')
        ->once()
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installMcp')
        ->with('herd', '/usr/bin/php', Mockery::type('array'), ['SITE_PATH' => base_path()])
        ->once()
        ->andReturn(true);

    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('mcpPath')
        ->once()
        ->andReturn('/path/to/herd-mcp.phar');

    $writer = new McpWriter($agent);
    $result = $writer->write(null, $herd);

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('throws exception when herd mcp installation returns false', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->twice()
        ->andReturn('php', '/usr/bin/php');
    $agent->shouldReceive('getArtisanPath')
        ->once()
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installMcp')
        ->with('herd', '/usr/bin/php', Mockery::type('array'), ['SITE_PATH' => base_path()])
        ->once()
        ->andReturn(false);

    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('mcpPath')
        ->once()
        ->andReturn('/path/to/herd-mcp.phar');

    $writer = new McpWriter($agent);

    expect(fn (): int => $writer->write(null, $herd))
        ->toThrow(RuntimeException::class, 'Failed to install Herd MCP: could not write configuration');
});

it('throws exception when herd mcp installation throws exception', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->twice()
        ->andReturn('php', '/usr/bin/php');
    $agent->shouldReceive('getArtisanPath')
        ->once()
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installMcp')
        ->with('herd', '/usr/bin/php', Mockery::type('array'), ['SITE_PATH' => base_path()])
        ->once()
        ->andThrow(new RuntimeException('Herd configuration error'));

    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('mcpPath')
        ->once()
        ->andReturn('/path/to/herd-mcp.phar');

    $writer = new McpWriter($agent);

    expect(fn (): int => $writer->write(null, $herd))
        ->toThrow(RuntimeException::class, 'Herd configuration error');
});

it('does not install herd mcp when herd is null', function (): void {
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

it('installs with both sail and herd', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'vendor/bin/sail', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('getPhpPath')
        ->withNoArgs()
        ->once()
        ->andReturn('/usr/bin/php');
    $agent->shouldReceive('installMcp')
        ->with('herd', '/usr/bin/php', Mockery::type('array'), ['SITE_PATH' => base_path()])
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

    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('mcpPath')
        ->once()
        ->andReturn('/path/to/herd-mcp.phar');

    $writer = new McpWriter($agent);
    $result = $writer->write($sail, $herd);

    expect($result)->toBe(McpWriter::SUCCESS);
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
    $result = $writer->write(null, null, $nightwatch);

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

    expect(fn (): int => $writer->write(null, null, $nightwatch))
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

it('installs with both herd and nightwatch', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('getPhpPath')
        ->twice()
        ->andReturn('php', '/usr/bin/php');
    $agent->shouldReceive('getArtisanPath')
        ->once()
        ->andReturn('artisan');
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installMcp')
        ->with('herd', '/usr/bin/php', Mockery::type('array'), ['SITE_PATH' => base_path()])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installHttpMcp')
        ->with('nightwatch', 'https://nightwatch.laravel.com/mcp')
        ->once()
        ->andReturn(true);

    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('mcpPath')
        ->once()
        ->andReturn('/path/to/herd-mcp.phar');

    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('mcpUrl')
        ->once()
        ->andReturn('https://nightwatch.laravel.com/mcp');

    $writer = new McpWriter($agent);
    $result = $writer->write(null, $herd, $nightwatch);

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('installs with sail, herd, and nightwatch', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'vendor/bin/sail', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('getPhpPath')
        ->withNoArgs()
        ->once()
        ->andReturn('/usr/bin/php');
    $agent->shouldReceive('installMcp')
        ->with('herd', '/usr/bin/php', Mockery::type('array'), ['SITE_PATH' => base_path()])
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

    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('mcpPath')
        ->once()
        ->andReturn('/path/to/herd-mcp.phar');

    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('mcpUrl')
        ->once()
        ->andReturn('https://nightwatch.laravel.com/mcp');

    $writer = new McpWriter($agent);
    $result = $writer->write($sail, $herd, $nightwatch);

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('installs svelte mcp when svelte is provided', function (): void {
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
        ->with('svelte', 'https://mcp.svelte.dev/mcp')
        ->once()
        ->andReturn(true);

    $svelte = Mockery::mock(Svelte::class);
    $svelte->shouldReceive('mcpUrl')
        ->once()
        ->andReturn('https://mcp.svelte.dev/mcp');

    $writer = new McpWriter($agent);
    $result = $writer->write(null, null, null, $svelte);

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('throws exception when svelte mcp installation returns false', function (): void {
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
        ->with('svelte', 'https://mcp.svelte.dev/mcp')
        ->once()
        ->andReturn(false);

    $svelte = Mockery::mock(Svelte::class);
    $svelte->shouldReceive('mcpUrl')
        ->once()
        ->andReturn('https://mcp.svelte.dev/mcp');

    $writer = new McpWriter($agent);

    expect(fn (): int => $writer->write(null, null, null, $svelte))
        ->toThrow(RuntimeException::class, 'Failed to install Svelte MCP: could not write configuration');
});

it('does not install svelte mcp when svelte is null', function (): void {
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

it('installs with all mcp servers: sail, herd, nightwatch, and svelte', function (): void {
    $agent = Mockery::mock(SupportsMcp::class);
    $agent->shouldReceive('installMcp')
        ->with('laravel-boost', 'vendor/bin/sail', ['artisan', 'boost:mcp'])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('getPhpPath')
        ->withNoArgs()
        ->once()
        ->andReturn('/usr/bin/php');
    $agent->shouldReceive('installMcp')
        ->with('herd', '/usr/bin/php', Mockery::type('array'), ['SITE_PATH' => base_path()])
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installHttpMcp')
        ->with('nightwatch', 'https://nightwatch.laravel.com/mcp')
        ->once()
        ->andReturn(true);
    $agent->shouldReceive('installHttpMcp')
        ->with('svelte', 'https://mcp.svelte.dev/mcp')
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

    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('mcpPath')
        ->once()
        ->andReturn('/path/to/herd-mcp.phar');

    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('mcpUrl')
        ->once()
        ->andReturn('https://nightwatch.laravel.com/mcp');

    $svelte = Mockery::mock(Svelte::class);
    $svelte->shouldReceive('mcpUrl')
        ->once()
        ->andReturn('https://mcp.svelte.dev/mcp');

    $writer = new McpWriter($agent);
    $result = $writer->write($sail, $herd, $nightwatch, $svelte);

    expect($result)->toBe(McpWriter::SUCCESS);
});
