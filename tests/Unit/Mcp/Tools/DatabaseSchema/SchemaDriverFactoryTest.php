<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\DatabaseSchema\MySQLSchemaDriver;
use Laravel\Boost\Mcp\Tools\DatabaseSchema\NullSchemaDriver;
use Laravel\Boost\Mcp\Tools\DatabaseSchema\PostgreSQLSchemaDriver;
use Laravel\Boost\Mcp\Tools\DatabaseSchema\SchemaDriverFactory;
use Laravel\Boost\Mcp\Tools\DatabaseSchema\SQLiteSchemaDriver;

beforeEach(function (): void {
    config()->set('database.connections.mysql_test', [
        'driver' => 'mysql',
        'database' => 'test_db',
        'prefix' => '',
    ]);
    config()->set('database.connections.mariadb_test', [
        'driver' => 'mariadb',
        'database' => 'test_db',
        'prefix' => '',
    ]);
    config()->set('database.connections.pgsql_test', [
        'driver' => 'pgsql',
        'database' => 'test_db',
        'prefix' => '',
    ]);
    config()->set('database.connections.sqlite_test', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    config()->set('database.connections.sqlsrv_test', [
        'driver' => 'sqlsrv',
        'database' => 'test_db',
        'prefix' => '',
    ]);
});

test('creates MySQLSchemaDriver for mysql connection', function (): void {
    $driver = SchemaDriverFactory::make('mysql_test');

    expect($driver)->toBeInstanceOf(MySQLSchemaDriver::class);
});

test('creates MySQLSchemaDriver for mariadb connection', function (): void {
    $driver = SchemaDriverFactory::make('mariadb_test');

    expect($driver)->toBeInstanceOf(MySQLSchemaDriver::class);
});

test('creates PostgreSQLSchemaDriver for pgsql connection', function (): void {
    $driver = SchemaDriverFactory::make('pgsql_test');

    expect($driver)->toBeInstanceOf(PostgreSQLSchemaDriver::class);
});

test('creates SQLiteSchemaDriver for sqlite connection', function (): void {
    $driver = SchemaDriverFactory::make('sqlite_test');

    expect($driver)->toBeInstanceOf(SQLiteSchemaDriver::class);
});

test('creates NullSchemaDriver for sqlsrv driver', function (): void {
    $driver = SchemaDriverFactory::make('sqlsrv_test');

    expect($driver)->toBeInstanceOf(NullSchemaDriver::class);
});
