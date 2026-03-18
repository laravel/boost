<?php

declare(strict_types=1);

// Feature: third-party-mcp-server-config, Property 9: For any McpServer, correct install method is called based on url vs command
// Feature: third-party-mcp-server-config, Property 10: For any McpServer collection, each server is installed with its name as the config key
// Feature: third-party-mcp-server-config, Property 11: For any failing server install, RuntimeException message contains server name

use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Install\McpServer;
use Laravel\Boost\Install\McpWriter;

/**
 * Property 9: For any McpServer, correct install method is called based on url vs command.
 * Runs 100 iterations with random server configurations.
 */
it('Property 9: routes to installHttpMcp for url-only servers, installMcp for command servers', function (): void {
    $serverNames = ['server-a', 'server-b', 'acme-tool', 'remote-mcp'];
    $commands = ['node', 'php', 'python', 'deno'];
    $urls = ['https://mcp.example.com', 'https://api.acme.com/mcp', 'https://tools.org/mcp'];

    for ($i = 0; $i < 100; $i++) {
        $name = $serverNames[array_rand($serverNames)].'-'.$i;
        $isHttp = (bool) random_int(0, 1);

        $agent = Mockery::mock(SupportsMcp::class);
        $agent->shouldReceive('getPhpPath')->andReturn('php');
        $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
        $agent->shouldReceive('installMcp')
            ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
            ->once()
            ->andReturn(true);

        if ($isHttp) {
            $url = $urls[array_rand($urls)];
            $server = new McpServer(name: $name, url: $url);

            $agent->shouldReceive('installHttpMcp')
                ->with($name, $url)
                ->once()
                ->andReturn(true);
        } else {
            $command = $commands[array_rand($commands)];
            $server = new McpServer(name: $name, command: $command);

            $agent->shouldReceive('installMcp')
                ->with($name, $command, [], [])
                ->once()
                ->andReturn(true);
        }

        $writer = new McpWriter($agent);
        $result = $writer->write(null, null, collect([$server]));

        expect($result)->toBe(McpWriter::SUCCESS, "Iteration {$i}: write() should return SUCCESS");

        Mockery::close();
    }
});

/**
 * Property 10: For any McpServer collection, each server is installed with its name as the config key.
 * Runs 50 iterations with random collections of servers.
 */
it('Property 10: each server is installed using its name as the config key', function (): void {
    for ($i = 0; $i < 50; $i++) {
        $count = random_int(1, 4);
        $servers = [];

        $agent = Mockery::mock(SupportsMcp::class);
        $agent->shouldReceive('getPhpPath')->andReturn('php');
        $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
        $agent->shouldReceive('installMcp')
            ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
            ->once()
            ->andReturn(true);

        for ($j = 0; $j < $count; $j++) {
            $name = "server-{$i}-{$j}";
            $servers[] = new McpServer(name: $name, command: 'node');

            $agent->shouldReceive('installMcp')
                ->with($name, 'node', [], [])
                ->once()
                ->andReturn(true);
        }

        $writer = new McpWriter($agent);
        $result = $writer->write(null, null, collect($servers));

        expect($result)->toBe(McpWriter::SUCCESS, "Iteration {$i}: write() should return SUCCESS");

        Mockery::close();
    }
});

/**
 * Property 11: For any failing server install, RuntimeException message contains server name.
 * Runs 100 iterations with random server names.
 */
it('Property 11: RuntimeException message contains server name on failed install', function (): void {
    $adjectives = ['fast', 'slow', 'smart', 'lazy', 'bright'];
    $nouns = ['server', 'tool', 'mcp', 'service', 'worker'];

    for ($i = 0; $i < 100; $i++) {
        $name = $adjectives[array_rand($adjectives)].'-'.$nouns[array_rand($nouns)].'-'.$i;

        $agent = Mockery::mock(SupportsMcp::class);
        $agent->shouldReceive('getPhpPath')->andReturn('php');
        $agent->shouldReceive('getArtisanPath')->andReturn('artisan');
        $agent->shouldReceive('installMcp')
            ->with('laravel-boost', 'php', ['artisan', 'boost:mcp'])
            ->once()
            ->andReturn(true);
        $agent->shouldReceive('installMcp')
            ->with($name, 'node', [], [])
            ->once()
            ->andReturn(false);

        $server = new McpServer(name: $name, command: 'node');
        $writer = new McpWriter($agent);

        $caught = null;
        try {
            $writer->write(null, null, collect([$server]));
        } catch (RuntimeException $e) {
            $caught = $e;
        }

        expect($caught)->not->toBeNull("Iteration {$i}: write() should throw RuntimeException")
            ->and($caught)->toBeInstanceOf(RuntimeException::class)
            ->and($caught->getMessage())->toContain($name,
                "Iteration {$i}: exception message must contain server name '{$name}'"
            );

        Mockery::close();
    }
});
