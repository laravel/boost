<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Laravel\Boost\Console\StartCommand;

it('invokes mcp:start with laravel-boost as the server name', function (): void {
    $mockArtisan = Mockery::mock();
    $mockArtisan->shouldReceive('call')
        ->once()
        ->with('mcp:start laravel-boost')
        ->andReturn(0);

    Artisan::swap($mockArtisan);

    $command = new StartCommand;

    expect($command->handle())->toBe(0);
});

it('returns the same exit code that mcp:start returns', function (): void {
    $mockArtisan = Mockery::mock();
    $mockArtisan->shouldReceive('call')
        ->once()
        ->with('mcp:start laravel-boost')
        ->andReturn(1);

    Artisan::swap($mockArtisan);

    $command = new StartCommand;

    expect($command->handle())->toBe(1);
});
