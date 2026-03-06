<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\ResolveContext;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

test('resolve-context returns error when no slices or bundles given', function (): void {
    $tool = app(ResolveContext::class);

    $response = $tool->handle(new Request([]));

    expect($response->isError())->toBeTrue();
});

test('resolve-context resolves a single slice', function (): void {
    $tool = app(ResolveContext::class);

    $response = $tool->handle(new Request([
        'slices' => ['db-connections' => []],
    ]));

    expect($response->isError())->toBeFalse();

    $text = $response->content()->toTool($tool)['text'] ?? '';

    expect($text)->toContain('=== db-connections ===');
});

test('resolve-context resolves multiple slices in one call', function (): void {
    $tool = app(ResolveContext::class);

    $response = $tool->handle(new Request([
        'slices' => [
            'db-connections' => [],
            'config-keys' => [],
        ],
    ]));

    $text = $response->content()->toTool($tool)['text'] ?? '';

    expect($text)->toContain('=== db-connections ===')
        ->and($text)->toContain('=== config-keys ===');
});

test('resolve-context passes parameters to slices', function (): void {
    $tool = app(ResolveContext::class);

    $response = $tool->handle(new Request([
        'slices' => ['get-config' => ['key' => 'app.name']],
    ]));

    $text = $response->content()->toTool($tool)['text'] ?? '';

    expect($text)->toContain('=== get-config ===')
        ->and($text)->toContain('Laravel');
});

test('resolve-context expands a bundle', function (): void {
    $tool = app(ResolveContext::class);

    $response = $tool->handle(new Request([
        'bundles' => ['@debug'],
    ]));

    $text = $response->content()->toTool($tool)['text'] ?? '';

    expect($text)->toContain('=== app-info ===');
});

test('resolve-context combines slices and bundles with deduplication', function (): void {
    $tool = app(ResolveContext::class);

    // Explicitly request app-info AND use @debug which includes app-info
    $response = $tool->handle(new Request([
        'slices' => ['app-info' => []],
        'bundles' => ['@debug'],
    ]));

    $text = $response->content()->toTool($tool)['text'] ?? '';

    // app-info should appear only once
    $count = substr_count($text, '=== app-info ===');
    expect($count)->toBe(1);
});

test('resolve-context reports failed slices in output', function (): void {
    $tool = app(ResolveContext::class);

    $response = $tool->handle(new Request([
        'slices' => [
            'nonexistent' => [],
            'db-connections' => [],
        ],
    ]));

    $text = $response->content()->toTool($tool)['text'] ?? '';

    expect($text)->toContain('=== db-connections ===')
        ->and($text)->toContain('[failed: nonexistent]')
        ->and($text)->not->toContain('=== nonexistent ===');
});

test('resolve-context normalizes non-array slice params', function (): void {
    $tool = app(ResolveContext::class);

    // Some LLMs might pass null or scalar instead of array for params
    $response = $tool->handle(new Request([
        'slices' => ['db-connections' => null],
    ]));

    expect($response->isError())->toBeFalse();

    $text = $response->content()->toTool($tool)['text'] ?? '';

    expect($text)->toContain('=== db-connections ===');
});
