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

test('getCheckConstraints filters on TABLE_NAME directly when the column exists', function (): void {
    $calls = [];

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('select')
        ->once()
        ->andReturnUsing(function (string $query, array $bindings) use (&$calls): array {
            $calls[] = [$query, $bindings];

            return [(object) ['CONSTRAINT_NAME' => 'orders_qty_positive']];
        });

    DB::shouldReceive('connection')->with('mysql_test')->andReturn($connection);

    $constraints = (new MySQLSchemaDriver('mysql_test'))->getCheckConstraints('orders');

    expect($constraints)->toHaveCount(1)
        ->and($calls)->toHaveCount(1)
        ->and($calls[0][0])->toMatch('/AND\s+TABLE_NAME\s*=\s*\?/')
        ->and($calls[0][0])->not->toContain('JOIN')
        ->and($calls[0][1])->toBe(['orders']);
});

test('getCheckConstraints maps the table through TABLE_CONSTRAINTS when TABLE_NAME is missing', function (): void {
    $calls = [];

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('select')
        ->twice()
        ->andReturnUsing(function (string $query, array $bindings) use (&$calls): array {
            $calls[] = [$query, $bindings];

            if (count($calls) === 1) {
                throw new Exception("Unknown column 'TABLE_NAME' in 'where clause'");
            }

            return [];
        });

    DB::shouldReceive('connection')->with('mysql_test')->andReturn($connection);

    (new MySQLSchemaDriver('mysql_test'))->getCheckConstraints('orders');

    expect($calls)->toHaveCount(2)
        ->and($calls[1][0])->toContain('JOIN information_schema.TABLE_CONSTRAINTS')
        ->and($calls[1][0])->toContain('tc.TABLE_NAME = ?')
        ->and($calls[1][0])->toContain("'CHECK'")
        ->and($calls[1][1])->toBe(['orders']);
});
