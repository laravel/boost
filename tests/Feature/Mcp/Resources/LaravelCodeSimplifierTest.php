<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Resources\LaravelCodeSimplifier\LaravelCodeSimplifier;

beforeEach(function (): void {
    $this->resource = new LaravelCodeSimplifier;
});

test('it has correct uri', function (): void {
    expect($this->resource->uri())->toBe('file://instructions/laravel-code-simplifier.md');
});

test('it has a description', function (): void {
    expect($this->resource->description())
        ->toContain('Simplifies')
        ->toContain('PHP/Laravel')
        ->toContain('maintainability');
});

test('it has markdown mime type', function (): void {
    expect($this->resource->mimeType())->toBe('text/markdown');
});

test('it returns a valid response', function (): void {
    $response = $this->resource->handle();

    expect($response)->isToolResult()
        ->toolHasNoError();
});

test('it contains core guideline content', function (): void {
    $response = $this->resource->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Laravel Code Simplifier')
        ->toolTextContains('Preserve Functionality')
        ->toolTextContains('Apply Project Standards')
        ->toolTextContains('Enhance Clarity');
});
