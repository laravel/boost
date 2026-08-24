<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Laravel\Boost\Mcp\Tools\DatabaseSchema\MySQLSchemaDriver;

test('getTables quotes the table type as a string literal', function (): void {
    $sql = null;

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('select')
        ->once()
        ->andReturnUsing(function (string $query) use (&$sql): array {
            $sql = $query;

            return [];
        });

    DB::shouldReceive('connection')->with('mysql_test')->andReturn($connection);

    (new MySQLSchemaDriver('mysql_test'))->getTables();

    // Double quotes are identifiers, not strings, when the server runs with ANSI_QUOTES.
    expect($sql)->toContain("'BASE TABLE'")
        ->and($sql)->not->toContain('"BASE TABLE"');
});

test('getCheckConstraints maps the table through TABLE_CONSTRAINTS', function (): void {
    $sql = null;
    $bindings = null;

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('select')
        ->once()
        ->andReturnUsing(function (string $query, array $queryBindings) use (&$sql, &$bindings): array {
            $sql = $query;
            $bindings = $queryBindings;

            return [];
        });

    DB::shouldReceive('connection')->with('mysql_test')->andReturn($connection);

    (new MySQLSchemaDriver('mysql_test'))->getCheckConstraints('orders');

    // MySQL's CHECK_CONSTRAINTS has no TABLE_NAME column, so filtering on it
    // there raises Unknown column and every table reports zero constraints.
    expect($sql)->toContain('JOIN information_schema.TABLE_CONSTRAINTS')
        ->and($sql)->toContain('tc.TABLE_NAME = ?')
        ->and($sql)->not->toMatch('/AND\s+TABLE_NAME\s*=/')
        ->and($bindings)->toBe(['orders', 'CHECK']);
});
