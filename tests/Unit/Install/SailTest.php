<?php

declare(strict_types=1);

use Laravel\Boost\Install\Sail;

it('builds mcp command with sail binary', function (): void {
    $sail = new Sail;

    $command = $sail->buildMcpCommand('laravel-boost');

    expect($command)->toBe([
        'key' => 'laravel-boost',
        'command' => 'vendor/bin/sail',
        'args' => ['artisan', 'boost:mcp'],
    ]);
});

it('builds mcp command for inside container', function (): void {
    $sail = new Sail;

    $command = $sail->buildMcpCommandInsideContainer('laravel-boost');

    expect($command)->toBe([
        'key' => 'laravel-boost',
        'command' => 'php',
        'args' => ['artisan', 'boost:mcp'],
    ]);
});
