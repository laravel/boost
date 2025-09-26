<?php

declare(strict_types=1);

use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Support\Config;

test('isRunningInWsl returns true when WSL_DISTRO_NAME is set', function (): void {
    putenv('WSL_DISTRO_NAME=Ubuntu');

    $command = new InstallCommand(new Config);
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('isRunningInWsl');

    expect($method->invoke($command))->toBeTrue();
});

test('isRunningInWsl returns true when IS_WSL is set', function (): void {
    putenv('IS_WSL=1');

    $command = new InstallCommand(new Config);
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('isRunningInWsl');

    expect($method->invoke($command))->toBeTrue();
});

test('isRunningInWsl returns true when both WSL env vars are set', function (): void {
    putenv('WSL_DISTRO_NAME=Ubuntu');
    putenv('IS_WSL=true');

    $command = new InstallCommand(new Config);
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('isRunningInWsl');

    expect($method->invoke($command))->toBeTrue();
});

test('isRunningInWsl returns false when no WSL env vars are set', function (): void {
    putenv('WSL_DISTRO_NAME');
    putenv('IS_WSL');

    $command = new InstallCommand(new Config);
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('isRunningInWsl');

    expect($method->invoke($command))->toBeFalse();
});

test('isRunningInWsl returns false when WSL env vars are empty strings', function (): void {
    putenv('WSL_DISTRO_NAME=');
    putenv('IS_WSL=');

    $command = new InstallCommand(new Config);
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('isRunningInWsl');

    expect($method->invoke($command))->toBeFalse();
});
