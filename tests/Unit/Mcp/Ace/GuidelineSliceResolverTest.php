<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\ContextSlice;
use Laravel\Boost\Mcp\Ace\GuidelineSliceResolver;
use Laravel\Boost\Mcp\Ace\SliceResult;

test('resolves a guideline slice by key', function (): void {
    $resolver = app(GuidelineSliceResolver::class);

    $slice = new ContextSlice(
        id: 'foundation',
        category: 'guidelines',
        label: 'Foundation guidelines',
        estimatedTokens: 200,
        isDynamic: false,
        guidelineKey: 'foundation',
    );

    $result = $resolver->resolve($slice);

    expect($result)->toBeInstanceOf(SliceResult::class)
        ->and($result->sliceId)->toBe('foundation')
        ->and($result->isError)->toBeFalse()
        ->and($result->content)->not->toBeEmpty();
});

test('returns error when guidelineKey is null', function (): void {
    $resolver = app(GuidelineSliceResolver::class);

    $slice = new ContextSlice(
        id: 'broken',
        category: 'guidelines',
        label: 'No key',
        estimatedTokens: 0,
        isDynamic: false,
    );

    $result = $resolver->resolve($slice);

    expect($result->isError)->toBeTrue()
        ->and($result->content)->toBe('');
});

test('returns error for non-existent guideline key', function (): void {
    $resolver = app(GuidelineSliceResolver::class);

    $slice = new ContextSlice(
        id: 'missing',
        category: 'guidelines',
        label: 'Missing guideline',
        estimatedTokens: 0,
        isDynamic: false,
        guidelineKey: 'this/does/not/exist',
    );

    $result = $resolver->resolve($slice);

    expect($result->isError)->toBeTrue();
});

test('resolves core guideline slices', function (): void {
    $resolver = app(GuidelineSliceResolver::class);

    foreach (['foundation', 'boost', 'php'] as $key) {
        $slice = new ContextSlice(
            id: $key,
            category: 'guidelines',
            label: $key,
            estimatedTokens: 200,
            isDynamic: false,
            guidelineKey: $key,
        );

        $result = $resolver->resolve($slice);

        expect($result->isError)->toBeFalse("Guideline '{$key}' should resolve without error")
            ->and($result->content)->not->toBeEmpty("Guideline '{$key}' should have content");
    }
});
