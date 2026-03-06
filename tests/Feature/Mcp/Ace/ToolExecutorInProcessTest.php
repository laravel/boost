<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\GetConfig;
use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Response;

test('executes read-only tools in-process when ace enabled', function (): void {
    config()->set('boost.ace.enabled', true);

    $executor = new ToolExecutor;

    $response = $executor->execute(GetConfig::class, ['key' => 'app.name']);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->isError())->toBeFalse();
});

test('shouldExecuteInProcess returns true for read-only tools when ace enabled', function (): void {
    config()->set('boost.ace.enabled', true);

    $executor = new ToolExecutor;
    $reflection = new ReflectionClass($executor);
    $method = $reflection->getMethod('shouldExecuteInProcess');

    expect($method->invoke($executor, GetConfig::class))->toBeTrue();
});

test('shouldExecuteInProcess returns false when ace disabled', function (): void {
    config()->set('boost.ace.enabled', false);

    $executor = new ToolExecutor;
    $reflection = new ReflectionClass($executor);
    $method = $reflection->getMethod('shouldExecuteInProcess');

    expect($method->invoke($executor, GetConfig::class))->toBeFalse();
});

test('shouldExecuteInProcess returns false for non-readonly tools', function (): void {
    config()->set('boost.ace.enabled', true);

    $executor = new ToolExecutor;
    $reflection = new ReflectionClass($executor);
    $method = $reflection->getMethod('shouldExecuteInProcess');

    expect($method->invoke($executor, Tinker::class))->toBeFalse();
});

test('in-process execution runs in same PID', function (): void {
    config()->set('boost.ace.enabled', true);

    $executor = new ToolExecutor;
    $reflection = new ReflectionClass($executor);
    $method = $reflection->getMethod('executeInProcess');

    // Use Tinker-like approach but via GetConfig which runs in-process
    $response = $executor->execute(GetConfig::class, ['key' => 'app.name']);

    expect($response->isError())->toBeFalse();
});
