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
