<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\SliceResult;

test('constructs success result', function (): void {
    $result = new SliceResult('db-schema', '{"tables": {}}');

    expect($result->sliceId)->toBe('db-schema')
        ->and($result->content)->toBe('{"tables": {}}')
        ->and($result->isError)->toBeFalse();
});

test('constructs error result', function (): void {
    $result = new SliceResult('db-schema', 'Connection failed', isError: true);

    expect($result->sliceId)->toBe('db-schema')
        ->and($result->content)->toBe('Connection failed')
        ->and($result->isError)->toBeTrue();
});
