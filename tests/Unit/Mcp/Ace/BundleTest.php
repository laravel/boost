<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\Bundle;

test('constructs with required properties', function (): void {
    $bundle = new Bundle(
        id: '@database-work',
        description: 'Database development context',
        sliceIds: ['db-schema', 'db-connections', 'app-info'],
        estimatedTokens: 630,
    );

    expect($bundle->id)->toBe('@database-work')
        ->and($bundle->description)->toBe('Database development context')
        ->and($bundle->sliceIds)->toBe(['db-schema', 'db-connections', 'app-info'])
        ->and($bundle->sliceParams)->toBe([])
        ->and($bundle->estimatedTokens)->toBe(630);
});

test('constructs with slice params', function (): void {
    $bundle = new Bundle(
        id: '@debug',
        description: 'Debugging context',
        sliceIds: ['last-error', 'browser-logs'],
        sliceParams: ['browser-logs' => ['entries' => 20]],
        estimatedTokens: 300,
    );

    expect($bundle->sliceParams)->toBe(['browser-logs' => ['entries' => 20]]);
});
