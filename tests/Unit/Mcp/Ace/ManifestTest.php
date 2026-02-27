<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\BundleRegistry;
use Laravel\Boost\Mcp\Ace\Manifest;
use Laravel\Boost\Mcp\Ace\SliceRegistry;

test('renders manifest with slices and bundles', function (): void {
    $manifest = new Manifest(app(SliceRegistry::class), new BundleRegistry);

    $output = $manifest->render();

    expect($output)->toContain('Available context')
        ->and($output)->toContain('resolve-context')
        ->and($output)->toContain('db-schema')
        ->and($output)->toContain('foundation')
        ->and($output)->toContain('Bundles')
        ->and($output)->toContain('@database-work')
        ->and($output)->toContain('@debug');
});

test('manifest includes category labels', function (): void {
    $manifest = new Manifest(app(SliceRegistry::class), new BundleRegistry);

    $output = $manifest->render();

    expect($output)->toContain('[database]')
        ->and($output)->toContain('[framework]')
        ->and($output)->toContain('[debug]')
        ->and($output)->toContain('[guidelines]');
});

test('manifest includes token estimates', function (): void {
    $manifest = new Manifest(app(SliceRegistry::class), new BundleRegistry);

    $output = $manifest->render();

    expect($output)->toContain('~250t')
        ->and($output)->toContain('~150t')
        ->and($output)->toContain('live');
});

test('manifest includes param indicators', function (): void {
    $manifest = new Manifest(app(SliceRegistry::class), new BundleRegistry);

    $output = $manifest->render();

    expect($output)->toContain('(param:')
        ->and($output)->toContain('filter');
});

test('manifest is compact', function (): void {
    $manifest = new Manifest(app(SliceRegistry::class), new BundleRegistry);

    $output = $manifest->render();
    $wordCount = str_word_count($output);

    // With dynamic guidelines the manifest grows but should stay under ~600 words
    expect($wordCount)->toBeLessThan(600);
});
