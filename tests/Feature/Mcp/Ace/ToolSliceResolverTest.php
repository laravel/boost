<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\ContextSlice;
use Laravel\Boost\Mcp\Ace\SliceResult;
use Laravel\Boost\Mcp\Ace\ToolSliceResolver;
use Laravel\Boost\Mcp\Tools\DatabaseConnections;
use Laravel\Boost\Mcp\Tools\GetConfig;

test('resolves tool slice in-process', function (): void {
    $resolver = app(ToolSliceResolver::class);

    $slice = new ContextSlice(
        id: 'db-connections',
        category: 'database',
        label: 'Database connections',
        estimatedTokens: 30,
        isDynamic: true,
        toolClass: DatabaseConnections::class,
    );

    $result = $resolver->resolve($slice);

    expect($result)->toBeInstanceOf(SliceResult::class)
        ->and($result->isError)->toBeFalse()
        ->and($result->sliceId)->toBe('db-connections')
        ->and($result->content)->not->toBeEmpty();
});

test('passes parameters to tool', function (): void {
    $resolver = app(ToolSliceResolver::class);

    $slice = new ContextSlice(
        id: 'get-config',
        category: 'config',
        label: 'Config value',
        estimatedTokens: 50,
        isDynamic: true,
        toolClass: GetConfig::class,
        params: ['key' => 'Config key'],
    );

    $result = $resolver->resolve($slice, ['key' => 'app.name']);

    expect($result->isError)->toBeFalse()
        ->and($result->content)->toContain('Laravel');
});

test('returns error for slice without tool class', function (): void {
    $resolver = app(ToolSliceResolver::class);

    $slice = new ContextSlice(
        id: 'test-slice',
        category: 'test',
        label: 'Test',
        estimatedTokens: 0,
        isDynamic: true,
    );

    $result = $resolver->resolve($slice);

    expect($result->isError)->toBeTrue();
});

test('catches exceptions from failing tools', function (): void {
    $resolver = app(ToolSliceResolver::class);

    $slice = new ContextSlice(
        id: 'get-config',
        category: 'config',
        label: 'Config value',
        estimatedTokens: 50,
        isDynamic: true,
        toolClass: GetConfig::class,
        params: ['key' => 'Config key'],
    );

    // Missing required 'key' parameter should not crash
    $result = $resolver->resolve($slice, []);

    // The tool may return an error or succeed with empty key - either way it shouldn't throw
    expect($result)->toBeInstanceOf(SliceResult::class);
});
