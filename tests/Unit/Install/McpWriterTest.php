<?php

declare(strict_types=1);

use JMac\Testing\Double;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Install\McpWriter;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;

it('installs boost mcp successfully without sail', function (): void {
    $agent = Double::for(SupportsMcp::class);
    $agent->expects('getPhpPath')->returns('php');
    $agent->expects('getArtisanPath')->returns('artisan');
    $agent->expects('installMcp')->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])->returns(true);

    $writer = new McpWriter($agent);
    $result = $writer->write();

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('installs boost mcp with sail', function (): void {
    $agent = Double::for(SupportsMcp::class);
    $agent->expects('installMcp')->with('laravel-boost', 'vendor/bin/sail', ['artisan', 'boost:mcp'])->returns(true);

    $sail = Double::for(Sail::class);
    $sail->expects('buildMcpCommand')->with('laravel-boost')->returns([
        'key' => 'laravel-boost',
        'command' => 'vendor/bin/sail',
        'args' => ['artisan', 'boost:mcp'],
    ]);

    $writer = new McpWriter($agent);
    $result = $writer->write($sail);

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('throws exception when boost mcp installation returns false', function (): void {
    $agent = Double::for(SupportsMcp::class);
    $agent->allows('getPhpPath')->returns('php');
    $agent->allows('getArtisanPath')->returns('artisan');
    $agent->expects('installMcp')->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])->returns(false);

    $writer = new McpWriter($agent);

    expect(fn (): int => $writer->write())
        ->toThrow(RuntimeException::class, 'Failed to install Boost MCP: could not write configuration');
});

it('throws exception when boost mcp installation throws exception', function (): void {
    $agent = Double::for(SupportsMcp::class);
    $agent->allows('getPhpPath')->returns('php');
    $agent->allows('getArtisanPath')->returns('artisan');
    $agent->expects('installMcp')->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])->throws(new RuntimeException('Permission denied'));

    $writer = new McpWriter($agent);

    expect(fn (): int => $writer->write())
        ->toThrow(RuntimeException::class, 'Permission denied');
});

it('installs nightwatch mcp when nightwatch is provided', function (): void {
    $agent = Double::for(SupportsMcp::class);
    $agent->expects('getPhpPath')->returns('php');
    $agent->expects('getArtisanPath')->returns('artisan');
    $agent->expects('installMcp')->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])->returns(true);
    $agent->expects('installHttpMcp')->with('nightwatch', 'https://nightwatch.laravel.com/mcp')->returns(true);

    $writer = new McpWriter($agent);
    $result = $writer->write(null, new Nightwatch);

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('throws exception when nightwatch mcp installation returns false', function (): void {
    $agent = Double::for(SupportsMcp::class);
    $agent->expects('getPhpPath')->returns('php');
    $agent->expects('getArtisanPath')->returns('artisan');
    $agent->expects('installMcp')->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])->returns(true);
    $agent->expects('installHttpMcp')->with('nightwatch', 'https://nightwatch.laravel.com/mcp')->returns(false);

    $writer = new McpWriter($agent);

    expect(fn (): int => $writer->write(null, new Nightwatch))
        ->toThrow(RuntimeException::class, 'Failed to install Nightwatch MCP: could not write configuration');
});

it('does not install nightwatch mcp when nightwatch is null', function (): void {
    $agent = Double::for(SupportsMcp::class);
    $agent->expects('getPhpPath')->returns('php');
    $agent->expects('getArtisanPath')->returns('artisan');
    $agent->expects('installMcp')->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])->returns(true);
    $agent->expects('installHttpMcp')->never();

    $writer = new McpWriter($agent);
    $result = $writer->write();

    expect($result)->toBe(McpWriter::SUCCESS);
});

it('installs with both sail and nightwatch', function (): void {
    $agent = Double::for(SupportsMcp::class);
    $agent->expects('installMcp')->with('laravel-boost', 'vendor/bin/sail', ['artisan', 'boost:mcp'])->returns(true);
    $agent->expects('installHttpMcp')->with('nightwatch', 'https://nightwatch.laravel.com/mcp')->returns(true);

    $sail = Double::for(Sail::class);
    $sail->expects('buildMcpCommand')->with('laravel-boost')->returns([
        'key' => 'laravel-boost',
        'command' => 'vendor/bin/sail',
        'args' => ['artisan', 'boost:mcp'],
    ]);

    $writer = new McpWriter($agent);
    $result = $writer->write($sail, new Nightwatch);

    expect($result)->toBe(McpWriter::SUCCESS);
});
