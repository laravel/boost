<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\Bundle;
use Laravel\Boost\Mcp\Ace\BundleRegistry;

test('returns all registered bundles', function (): void {
    $registry = new BundleRegistry;

    $bundles = $registry->all();

    expect($bundles)->not->toBeEmpty()
        ->and($bundles->count())->toBe(4);
});

test('all bundles are Bundle instances', function (): void {
    $registry = new BundleRegistry;

    $registry->all()->each(function ($bundle): void {
        expect($bundle)->toBeInstanceOf(Bundle::class);
    });
});

test('can get bundle by id', function (): void {
    $registry = new BundleRegistry;

    $bundle = $registry->get('@database-work');

    expect($bundle)->toBeInstanceOf(Bundle::class)
        ->and($bundle->id)->toBe('@database-work')
        ->and($bundle->sliceIds)->toContain('db-schema', 'db-connections', 'app-info', 'foundation');
});

test('returns null for unknown bundle', function (): void {
    $registry = new BundleRegistry;

    expect($registry->get('@nonexistent'))->toBeNull();
});

test('has returns true for existing bundles', function (): void {
    $registry = new BundleRegistry;

    expect($registry->has('@database-work'))->toBeTrue()
        ->and($registry->has('@testing'))->toBeTrue()
        ->and($registry->has('@debug'))->toBeTrue()
        ->and($registry->has('@new-feature'))->toBeTrue();
});

test('debug bundle includes default params for browser-logs', function (): void {
    $registry = new BundleRegistry;

    $bundle = $registry->get('@debug');

    expect($bundle->sliceParams)->toHaveKey('browser-logs')
        ->and($bundle->sliceParams['browser-logs'])->toBe(['entries' => 20]);
});

test('caches bundles across calls', function (): void {
    $registry = new BundleRegistry;

    $first = $registry->all();
    $second = $registry->all();

    expect($first)->toBe($second);
});

test('register adds a custom bundle', function (): void {
    $registry = new BundleRegistry;

    $registry->register(new Bundle(
        id: '@custom',
        description: 'Custom bundle',
        sliceIds: ['app-info', 'routes'],
        estimatedTokens: 350,
    ));

    expect($registry->has('@custom'))->toBeTrue()
        ->and($registry->get('@custom')->sliceIds)->toBe(['app-info', 'routes'])
        ->and($registry->all()->count())->toBe(5);
});

test('register clears cache so new bundle appears', function (): void {
    $registry = new BundleRegistry;

    $before = $registry->all()->count();

    $registry->register(new Bundle(
        id: '@late-add',
        description: 'Added after first access',
        sliceIds: ['db-schema'],
    ));

    expect($registry->all()->count())->toBe($before + 1);
});

test('register can override a built-in bundle', function (): void {
    $registry = new BundleRegistry;

    $registry->register(new Bundle(
        id: '@debug',
        description: 'Overridden debug',
        sliceIds: ['last-error'],
        estimatedTokens: 100,
    ));

    $bundle = $registry->get('@debug');

    expect($bundle->description)->toBe('Overridden debug')
        ->and($bundle->sliceIds)->toBe(['last-error']);
});

test('exclude config filters out bundles', function (): void {
    config()->set('boost.ace.bundles.exclude', ['@debug', '@testing']);

    $registry = new BundleRegistry;

    expect($registry->has('@debug'))->toBeFalse()
        ->and($registry->has('@testing'))->toBeFalse()
        ->and($registry->has('@database-work'))->toBeTrue()
        ->and($registry->has('@new-feature'))->toBeTrue()
        ->and($registry->all()->count())->toBe(2);
});
