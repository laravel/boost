<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Mcp\Request;

it('executes allowed read-only queries', function (): void {
    DB::shouldReceive('connection')->andReturnSelf();
    DB::shouldReceive('select')->andReturn([]);
    DB::shouldReceive('getTablePrefix')->andReturn('');

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

it('blocks destructive queries', function (): void {
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

it('blocks extended destructive keywords for mysql postgres and sqlite', function (): void {
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

it('handles empty queries gracefully', function (): void {
    $tool = new DatabaseQuery;

    foreach (['', '   ', "\n\t"] as $query) {
        $response = $tool->handle(new Request(['query' => $query]));
        expect($response)->isToolResult()
            ->toolHasError()
            ->toolTextContains('Please pass a valid query');
    }
});

it('allows queries starting with any allowed keyword even when identifiers look like SQL keywords', function (): void {
    DB::shouldReceive('connection')->andReturnSelf();
    DB::shouldReceive('select')->andReturn([]);
    DB::shouldReceive('getTablePrefix')->andReturn('');

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

it('adds table prefix to queries', function (): void {
    DB::shouldReceive('connection')->andReturnSelf();
    DB::shouldReceive('getTablePrefix')->andReturn('wp_');

    $testCases = [
        'SELECT * FROM users' => 'SELECT * FROM wp_users',
        'SELECT * FROM users JOIN posts ON users.id = posts.user_id' => 'SELECT * FROM wp_users JOIN wp_posts ON users.id = posts.user_id',
        'SELECT * FROM `users`' => 'SELECT * FROM `wp_users`',
        'SELECT * FROM wp_already_prefixed' => 'SELECT * FROM wp_already_prefixed',
        'EXPLAIN SELECT * FROM users' => 'EXPLAIN SELECT * FROM wp_users',
        'WITH cte AS (SELECT 1) SELECT * FROM users' => 'WITH cte AS (SELECT 1) SELECT * FROM wp_users',
        'SELECT * FROM users JOIN posts ON users.id = posts.user_id JOIN comments ON posts.id = comments.post_id' => 'SELECT * FROM wp_users JOIN wp_posts ON users.id = posts.user_id JOIN wp_comments ON posts.id = comments.post_id',
        'SELECT * FROM "users"' => 'SELECT * FROM "wp_users"',
        'WITH cte AS (SELECT * FROM users) SELECT * FROM cte' => 'WITH cte AS (SELECT * FROM wp_users) SELECT * FROM cte',
        'WITH cte1 AS (SELECT * FROM users), cte2 AS (SELECT * FROM posts) SELECT * FROM cte1 JOIN cte2' => 'WITH cte1 AS (SELECT * FROM wp_users), cte2 AS (SELECT * FROM wp_posts) SELECT * FROM cte1 JOIN cte2',
        'WITH RECURSIVE cte AS (SELECT * FROM users) SELECT * FROM cte' => 'WITH RECURSIVE cte AS (SELECT * FROM wp_users) SELECT * FROM cte',
        'WITH cte (id, name) AS (SELECT id, name FROM users) SELECT * FROM cte' => 'WITH cte (id, name) AS (SELECT id, name FROM wp_users) SELECT * FROM cte',
        'WITH cte AS (SELECT * FROM users JOIN posts ON users.id = posts.user_id) SELECT * FROM cte' => 'WITH cte AS (SELECT * FROM wp_users JOIN wp_posts ON users.id = posts.user_id) SELECT * FROM cte',
        'WITH cte1 AS (SELECT * FROM users), cte2 AS (SELECT * FROM posts WHERE user_id IN (SELECT id FROM cte1)) SELECT * FROM cte2' => 'WITH cte1 AS (SELECT * FROM wp_users), cte2 AS (SELECT * FROM wp_posts WHERE user_id IN (SELECT id FROM cte1)) SELECT * FROM cte2',
        'WITH active_users AS (SELECT * FROM users WHERE active = 1) SELECT * FROM posts JOIN active_users ON posts.user_id = active_users.id' => 'WITH active_users AS (SELECT * FROM wp_users WHERE active = 1) SELECT * FROM wp_posts JOIN active_users ON posts.user_id = active_users.id',
        'WITH cte1 AS (SELECT * FROM users), cte2 AS (SELECT * FROM cte1 WHERE id > 10) SELECT * FROM cte2' => 'WITH cte1 AS (SELECT * FROM wp_users), cte2 AS (SELECT * FROM cte1 WHERE id > 10) SELECT * FROM cte2',
        'WITH cte AS (SELECT * FROM users UNION SELECT * FROM archived_users) SELECT * FROM cte' => 'WITH cte AS (SELECT * FROM wp_users UNION SELECT * FROM wp_archived_users) SELECT * FROM cte',
        'WITH users_cte AS (SELECT * FROM users), posts_cte AS (SELECT p.* FROM posts p JOIN users_cte u ON p.user_id = u.id) SELECT * FROM posts_cte' => 'WITH users_cte AS (SELECT * FROM wp_users), posts_cte AS (SELECT p.* FROM wp_posts p JOIN users_cte u ON p.user_id = u.id) SELECT * FROM posts_cte',
        'WITH users AS (SELECT * FROM employees) SELECT * FROM users' => 'WITH users AS (SELECT * FROM wp_employees) SELECT * FROM users',
        'WITH RECURSIVE cte1 AS (SELECT * FROM users), RECURSIVE cte2 AS (SELECT * FROM posts) SELECT * FROM cte1 JOIN cte2' => 'WITH RECURSIVE cte1 AS (SELECT * FROM wp_users), RECURSIVE cte2 AS (SELECT * FROM wp_posts) SELECT * FROM cte1 JOIN cte2',
        'DESCRIBE users' => 'DESCRIBE wp_users',
        'DESC users' => 'DESC wp_users',
        'DESCRIBE `users`' => 'DESCRIBE `wp_users`',
    ];

    foreach ($testCases as $input => $expected) {
        DB::shouldReceive('select')->with($expected)->once()->andReturn([]);

        $tool = new DatabaseQuery;
        $response = $tool->handle(new Request(['query' => $input]));

        expect($response)->isToolResult()->toolHasNoError();
    }
});
