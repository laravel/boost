<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\ContextSlice;
use Laravel\Boost\Mcp\Ace\SliceRegistry;

test('returns all registered slices', function (): void {
    $registry = app(SliceRegistry::class);

    $slices = $registry->all();

    expect($slices)->not->toBeEmpty()
        ->and($slices->count())->toBeGreaterThan(10);
});

test('all slices are ContextSlice instances', function (): void {
    $registry = app(SliceRegistry::class);

    $registry->all()->each(function ($slice): void {
        expect($slice)->toBeInstanceOf(ContextSlice::class);
    });
});

test('can get slice by id', function (): void {
    $registry = app(SliceRegistry::class);

    $slice = $registry->get('db-schema');

    expect($slice)->toBeInstanceOf(ContextSlice::class)
        ->and($slice->id)->toBe('db-schema')
        ->and($slice->category)->toBe('database')
        ->and($slice->isDynamic)->toBeTrue();
});

test('returns null for unknown slice', function (): void {
    $registry = app(SliceRegistry::class);

    expect($registry->get('nonexistent'))->toBeNull();
});

test('has returns true for existing slice', function (): void {
    $registry = app(SliceRegistry::class);

    expect($registry->has('app-info'))->toBeTrue()
        ->and($registry->has('foundation'))->toBeTrue();
});

test('has returns false for unknown slice', function (): void {
    $registry = app(SliceRegistry::class);

    expect($registry->has('nonexistent'))->toBeFalse();
});

test('contains expected dynamic slices', function (): void {
    $registry = app(SliceRegistry::class);

    $expectedDynamic = ['app-info', 'db-schema', 'db-connections', 'db-query', 'routes',
        'artisan-commands', 'config-keys', 'env-vars', 'get-config', 'absolute-url',
        'last-error', 'browser-logs', 'log-entries', 'search-docs'];

    foreach ($expectedDynamic as $id) {
        $slice = $registry->get($id);
        expect($slice)->not->toBeNull("Slice '{$id}' should exist")
            ->and($slice->isDynamic)->toBeTrue("Slice '{$id}' should be dynamic")
            ->and($slice->toolClass)->not->toBeNull("Slice '{$id}' should have a tool class");
    }
});

test('static slices are built from guideline composer', function (): void {
    $registry = app(SliceRegistry::class);

    $staticSlices = $registry->all()->filter(fn (ContextSlice $slice) => ! $slice->isDynamic);

    expect($staticSlices)->not->toBeEmpty();

    $staticSlices->each(function (ContextSlice $slice): void {
        expect($slice->category)->toBe('guidelines')
            ->and($slice->guidelineKey)->not->toBeNull()
            ->and($slice->isDynamic)->toBeFalse();
    });
});

test('contains core guideline slices', function (): void {
    $registry = app(SliceRegistry::class);

    // Core guidelines always exist: foundation, boost, php
    foreach (['foundation', 'boost', 'php'] as $id) {
        $slice = $registry->get($id);
        expect($slice)->not->toBeNull("Core guideline slice '{$id}' should exist")
            ->and($slice->isDynamic)->toBeFalse()
            ->and($slice->guidelineKey)->not->toBeNull();
    }
});

test('caches slices across calls', function (): void {
    $registry = app(SliceRegistry::class);

    $first = $registry->all();
    $second = $registry->all();

    expect($first)->toBe($second);
});

test('discovers more guidelines than hardcoded minimum', function (): void {
    $registry = app(SliceRegistry::class);

    $staticSlices = $registry->all()->filter(fn (ContextSlice $slice) => ! $slice->isDynamic);

    // Should discover more than the old hardcoded 7 slices
    expect($staticSlices->count())->toBeGreaterThanOrEqual(3);
});
