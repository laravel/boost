<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\ListAvailableConfigKeys;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    config()->set('test.simple', 'value');
    config()->set('test.nested.key', 'nested_value');
    config()->set('test.array', ['item1', 'item2']);
});

test('it returns list of config keys in dot notation', function (): void {
    $tool = new ListAvailableConfigKeys;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($content): void {
            expect($content)->toBeArray()
                ->not->toBeEmpty()
                ->toContain(
                    // Check that it constains common Laravel config keys
                    'app.name',
                    'app.env',
                    'database.default',
                    // Check that it contains our test keys
                    'test.simple',
                    'test.nested.key',
                    'test.array.0',
                    'test.array.1'
                );

            // Check that keys are sorted
            $sortedContent = $content;
            sort($sortedContent);
            expect($content)->toBe($sortedContent);
        });
});

test('it handles empty config gracefully', function (): void {
    // Clear all config
    config()->set('test');

    $tool = new ListAvailableConfigKeys;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($content): void {
            expect($content)->toBeArray()
                // Should still have Laravel default config keys
                ->toContain('app.name');
        });
});
