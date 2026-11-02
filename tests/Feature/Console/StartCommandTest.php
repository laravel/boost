<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use JMac\Testing\Double;
use Laravel\Boost\Console\StartCommand;
use Symfony\Component\Console\Command\Command;

it('invokes mcp:start with laravel-boost as the server name', function (): void {
    $mockArtisan = Double::for(Kernel::class);
    $mockArtisan->expects('call')->with('mcp:start laravel-boost')->returns(0);

    Artisan::swap($mockArtisan);

    $command = new StartCommand;

    expect($command->handle())->toBe(Command::SUCCESS);
});

it('returns the same exit code that mcp:start returns', function (): void {
    $mockArtisan = Double::for(Kernel::class);
    $mockArtisan->expects('call')->with('mcp:start laravel-boost')->returns(Command::FAILURE);

    Artisan::swap($mockArtisan);

    $command = new StartCommand;

    expect($command->handle())->toBe(Command::FAILURE);
});
