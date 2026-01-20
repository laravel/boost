<?php

declare(strict_types=1);

use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Install\McpWriter;

afterEach(function (): void {
    putenv('WSL_DISTRO_NAME');
    putenv('IS_WSL');
});

test('isRunningInsideWsl returns true when WSL_DISTRO_NAME is set', function (): void {
    putenv('WSL_DISTRO_NAME=Ubuntu');

    $agent = Mockery::mock(SupportsMcp::class);
    $writer = new McpWriter($agent);
    $reflection = new ReflectionClass($writer);
    $method = $reflection->getMethod('isRunningInsideWsl');

    expect($method->invoke($writer))->toBeTrue();
});

test('isRunningInsideWsl returns true when IS_WSL is set', function (): void {
    putenv('IS_WSL=1');

    $agent = Mockery::mock(SupportsMcp::class);
    $writer = new McpWriter($agent);
    $reflection = new ReflectionClass($writer);
    $method = $reflection->getMethod('isRunningInsideWsl');

    expect($method->invoke($writer))->toBeTrue();
});

test('isRunningInsideWsl returns true when both WSL env vars are set', function (): void {
    putenv('WSL_DISTRO_NAME=Ubuntu');
    putenv('IS_WSL=true');

    $agent = Mockery::mock(SupportsMcp::class);
    $writer = new McpWriter($agent);
    $reflection = new ReflectionClass($writer);
    $method = $reflection->getMethod('isRunningInsideWsl');

    expect($method->invoke($writer))->toBeTrue();
});

test('isRunningInsideWsl returns false when no WSL env vars are set', function (): void {
    putenv('WSL_DISTRO_NAME');
    putenv('IS_WSL');

    $agent = Mockery::mock(SupportsMcp::class);
    $writer = new McpWriter($agent);
    $reflection = new ReflectionClass($writer);
    $method = $reflection->getMethod('isRunningInsideWsl');

    expect($method->invoke($writer))->toBeFalse();
});

test('isRunningInsideWsl returns false when WSL env vars are empty strings', function (): void {
    putenv('WSL_DISTRO_NAME=');
    putenv('IS_WSL=');

    $agent = Mockery::mock(SupportsMcp::class);
    $writer = new McpWriter($agent);
    $reflection = new ReflectionClass($writer);
    $method = $reflection->getMethod('isRunningInsideWsl');

    expect($method->invoke($writer))->toBeFalse();
});
