<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Mcp\Request;

test('executes allowed read-only queries', function (): void {
    DB::shouldReceive('connection')->andReturnSelf();
    DB::shouldReceive('select')->andReturn([]);

    $tool = new DatabaseQuery;

    $queries = [
        'SELECT * FROM users',
        'SHOW TABLES',
        'EXPLAIN SELECT * FROM users',
        'DESCRIBE users',
        'DESC users',
        'VALUES (1, 2, 3)',
        'TABLE users',
        'WITH cte AS (SELECT * FROM users) SELECT * FROM cte',
    ];

    foreach ($queries as $query) {
        $response = $tool->handle(new Request(['query' => $query]));
        expect($response)->isToolResult()
            ->toolHasNoError();
    }
});

test('blocks destructive queries', function (): void {
    DB::shouldReceive('select')->never();

    $tool = new DatabaseQuery;

    $queries = [
        'DELETE FROM users',
        'UPDATE users SET name = "x"',
        'INSERT INTO users VALUES (1)',
        'DROP TABLE users',
    ];

    foreach ($queries as $query) {
        $response = $tool->handle(new Request(['query' => $query]));
        expect($response)->isToolResult()
            ->toolHasError()
            ->toolTextContains('Only read-only queries are allowed');
    }
});

test('blocks extended destructive keywords for mysql postgres and sqlite', function (): void {
    DB::shouldReceive('select')->never();

    $tool = new DatabaseQuery;

    $queries = [
        'REPLACE INTO users VALUES (1)',
        'TRUNCATE TABLE users',
        'ALTER TABLE users ADD COLUMN age INT',
        'CREATE TABLE hackers (id INT)',
        'RENAME TABLE users TO old_users',
    ];

    foreach ($queries as $query) {
        $response = $tool->handle(new Request(['query' => $query]));
        expect($response)->isToolResult()
            ->toolHasError()
            ->toolTextContains('Only read-only queries are allowed');
    }
});

test('handles empty queries gracefully', function (): void {
    $tool = new DatabaseQuery;

    foreach (['', '   ', "\n\t"] as $query) {
        $response = $tool->handle(new Request(['query' => $query]));
        expect($response)->isToolResult()
            ->toolHasError()
            ->toolTextContains('Please pass a valid query');
    }
});

test('allows queries starting with any allowed keyword even when identifiers look like SQL keywords', function (): void {
    DB::shouldReceive('connection')->andReturnSelf();
    DB::shouldReceive('select')->andReturn([]);

    $tool = new DatabaseQuery;

    $queries = [
        'SELECT * FROM delete',
        'SHOW TABLES LIKE "drop"',
        'EXPLAIN SELECT * FROM update',
        'DESCRIBE delete_log',
        'DESC update_history',
        'WITH delete_cte AS (SELECT 1) SELECT * FROM delete_cte',
        'VALUES (1), (2)',
        'TABLE update',
    ];

    foreach ($queries as $query) {
        $response = $tool->handle(new Request([
            'query' => $query,
        ]));

        expect($response)->isToolResult()
            ->toolHasNoError();
    }
});
