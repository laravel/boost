<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\ListAvailableConfigKeys;

beforeEach(function () {
    config()->set('test.simple', 'value');
    config()->set('test.nested.key', 'nested_value');
    config()->set('test.array', ['item1', 'item2']);
});

test('it returns list of config keys in dot notation', function () {
    $tool = new ListAvailableConfigKeys;
    $result = $tool->handle([]);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($content) {
            expect($content)->toBeArray()
                ->and($content)->not->toBeEmpty()
                // Check that it contains common Laravel config keys
                ->and($content)->toContain('app.name')
                ->and($content)->toContain('app.env')
                ->and($content)->toContain('database.default')
                // Check that it contains our test keys
                ->and($content)->toContain('test.simple')
                ->and($content)->toContain('test.nested.key')
                ->and($content)->toContain('test.array.0')
                ->and($content)->toContain('test.array.1');

            // Check that keys are sorted
            $sortedContent = $content;
            sort($sortedContent);
            expect($content)->toBe($sortedContent);
        });
});

test('it handles empty config gracefully', function () {
    // Clear all config
    config()->set('test', null);

    $tool = new ListAvailableConfigKeys;
    $result = $tool->handle([]);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($content) {
            expect($content)->toBeArray()
                // Should still have Laravel default config keys
                ->and($content)->toContain('app.name');
        });
});
