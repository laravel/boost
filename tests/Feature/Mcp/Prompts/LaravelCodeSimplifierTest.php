<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Prompts\LaravelCodeSimplifier\LaravelCodeSimplifier;

beforeEach(function (): void {
    $this->prompt = new LaravelCodeSimplifier;
});

test('it has correct name', function (): void {
    expect($this->prompt->name())->toBe('laravel-code-simplifier');
});

test('it has a description', function (): void {
    expect($this->prompt->description())
        ->toContain('Simplifies')
        ->toContain('PHP/Laravel')
        ->toContain('maintainability');
});

test('it returns a valid response', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolHasNoError();
});

test('it contains core guideline content', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Laravel Code Simplifier')
        ->toolTextContains('Preserve Functionality')
        ->toolTextContains('Apply Project Standards')
        ->toolTextContains('Enhance Clarity');
});
