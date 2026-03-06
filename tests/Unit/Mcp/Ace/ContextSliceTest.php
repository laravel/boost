<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Ace\ContextSlice;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;

test('constructs with required properties', function (): void {
    $slice = new ContextSlice(
        id: 'db-schema',
        category: 'database',
        label: 'Table structures',
        estimatedTokens: 250,
        isDynamic: true,
    );

    expect($slice->id)->toBe('db-schema')
        ->and($slice->category)->toBe('database')
        ->and($slice->label)->toBe('Table structures')
        ->and($slice->estimatedTokens)->toBe(250)
        ->and($slice->isDynamic)->toBeTrue()
        ->and($slice->guidelineKey)->toBeNull()
        ->and($slice->toolClass)->toBeNull()
        ->and($slice->params)->toBe([]);
});

test('hasParams returns true when params exist', function (): void {
    $slice = new ContextSlice(
        id: 'db-schema',
        category: 'database',
        label: 'Table structures',
        estimatedTokens: 250,
        isDynamic: true,
        toolClass: DatabaseSchema::class,
        params: ['filter' => 'Filter by name'],
    );

    expect($slice->hasParams())->toBeTrue();
});

test('hasParams returns false when no params', function (): void {
    $slice = new ContextSlice(
        id: 'app-info',
        category: 'framework',
        label: 'Application info',
        estimatedTokens: 150,
        isDynamic: true,
    );

    expect($slice->hasParams())->toBeFalse();
});

