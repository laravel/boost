<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\BundleRegistry;
use Laravel\Boost\Mcp\Ace\ContextResolver;
use Laravel\Boost\Mcp\Ace\GuidelineSliceResolver;
use Laravel\Boost\Mcp\Ace\SliceRegistry;
use Laravel\Boost\Mcp\Ace\SliceResult;
use Laravel\Boost\Mcp\Ace\ToolSliceResolver;

test('resolves a single dynamic slice', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve(['db-connections' => []]);

    expect($results)->toHaveCount(1)
        ->and($results->has('db-connections'))->toBeTrue()
        ->and($results->get('db-connections'))->toBeInstanceOf(SliceResult::class)
        ->and($results->get('db-connections')->isError)->toBeFalse()
        ->and($results->get('db-connections')->content)->not->toBeEmpty();
});

test('resolves multiple slices at once', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([
        'db-connections' => [],
        'config-keys' => [],
    ]);

    expect($results)->toHaveCount(2)
        ->and($results->get('db-connections')->isError)->toBeFalse()
        ->and($results->get('config-keys')->isError)->toBeFalse();
});

test('resolves slices with parameters', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([
        'get-config' => ['key' => 'app.name'],
    ]);

    expect($results)->toHaveCount(1)
        ->and($results->get('get-config')->isError)->toBeFalse()
        ->and($results->get('get-config')->content)->toContain('Laravel');
});

test('expands @debug bundle into slices', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([], ['@debug']);

    // @debug = last-error + browser-logs + app-info
    expect($results)->toHaveCount(3)
        ->and($results->has('last-error'))->toBeTrue()
        ->and($results->has('browser-logs'))->toBeTrue()
        ->and($results->has('app-info'))->toBeTrue();
});

test('expands @database-work bundle without errors', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([], ['@database-work']);

    // Every slice in the bundle should resolve (no "Unknown slice" errors)
    $results->each(function (SliceResult $result) {
        expect($result->content)->not->toContain('Unknown slice');
    });

    expect($results->has('db-schema'))->toBeTrue()
        ->and($results->has('db-connections'))->toBeTrue()
        ->and($results->has('app-info'))->toBeTrue()
        ->and($results->has('foundation'))->toBeTrue();
});

test('expands @testing bundle without errors', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([], ['@testing']);

    $results->each(function (SliceResult $result) {
        expect($result->content)->not->toContain('Unknown slice');
    });

    expect($results->has('app-info'))->toBeTrue()
        ->and($results->has('db-schema'))->toBeTrue();
});

test('expands @new-feature bundle without errors', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([], ['@new-feature']);

    $results->each(function (SliceResult $result) {
        expect($result->content)->not->toContain('Unknown slice');
    });

    expect($results->has('foundation'))->toBeTrue()
        ->and($results->has('app-info'))->toBeTrue()
        ->and($results->has('db-schema'))->toBeTrue()
        ->and($results->has('routes'))->toBeTrue();
});

test('deduplicates slices from bundles and explicit requests', function (): void {
    $resolver = app(ContextResolver::class);

    // Explicitly request app-info AND use @debug bundle (which includes app-info)
    $results = $resolver->resolve(
        ['app-info' => []],
        ['@debug']
    );

    // Should still only have 3 entries (not 4)
    expect($results)->toHaveCount(3)
        ->and($results->has('app-info'))->toBeTrue();
});

test('returns error for unknown slice', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve(['nonexistent' => []]);

    expect($results)->toHaveCount(1)
        ->and($results->get('nonexistent')->isError)->toBeTrue()
        ->and($results->get('nonexistent')->content)->toContain('Unknown slice');
});

test('ignores unknown bundles', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve(
        ['db-connections' => []],
        ['@nonexistent']
    );

    // Only the explicit slice should be resolved
    expect($results)->toHaveCount(1)
        ->and($results->has('db-connections'))->toBeTrue();
});

test('formats results with section markers', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([
        'db-connections' => [],
        'config-keys' => [],
    ]);

    $formatted = $resolver->format($results);

    expect($formatted)->toContain('=== db-connections ===')
        ->and($formatted)->toContain('=== config-keys ===');
});

test('format excludes error content but appends failure summary', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([
        'nonexistent' => [],
        'db-connections' => [],
    ]);

    $formatted = $resolver->format($results);

    expect($formatted)->toContain('=== db-connections ===')
        ->and($formatted)->not->toContain('=== nonexistent ===')
        ->and($formatted)->not->toContain('Unknown slice')
        ->and($formatted)->toContain('[failed: nonexistent]');
});

test('format has no failure summary when all slices succeed', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([
        'db-connections' => [],
        'config-keys' => [],
    ]);

    $formatted = $resolver->format($results);

    expect($formatted)->not->toContain('[failed:');
});

test('error in one slice does not affect other slices', function (): void {
    $resolver = app(ContextResolver::class);

    $results = $resolver->resolve([
        'nonexistent' => [],
        'db-connections' => [],
    ]);

    expect($results)->toHaveCount(2)
        ->and($results->get('nonexistent')->isError)->toBeTrue()
        ->and($results->get('db-connections')->isError)->toBeFalse()
        ->and($results->get('db-connections')->content)->not->toBeEmpty();
});
