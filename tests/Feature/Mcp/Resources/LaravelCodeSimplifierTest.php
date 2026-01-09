<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Resources\LaravelCodeSimplifier;

test('it has correct uri', function (): void {
    $resource = new LaravelCodeSimplifier;

    expect($resource->uri())->toBe('file://instructions/laravel-code-simplifier.md');
});

test('it has a description', function (): void {
    $resource = new LaravelCodeSimplifier;

    expect($resource->description())->not->toBeEmpty();
});

test('it has markdown mime type', function (): void {
    $resource = new LaravelCodeSimplifier;

    expect($resource->mimeType())->toBe('text/markdown');
});

test('it returns a valid response', function (): void {
    $resource = new LaravelCodeSimplifier;

    $response = $resource->handle();

    expect($response)->isToolResult()
        ->toolHasNoError();
});
