<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Boost\Mcp\Tools\DatabaseQuery;
use Laravel\Mcp\Request;
use Mockery\MockInterface;
use MongoDB\BSON\Int64;
use MongoDB\Database;
use MongoDB\Driver\CursorInterface;
use MongoDB\Driver\Server;
use MongoDB\Laravel\Connection as MongoDBConnection;

function fakeConnection(): MockInterface
{
    $connection = Mockery::mock(ConnectionInterface::class);
    DB::shouldReceive('connection')->andReturn($connection);

    return $connection;
}

function expectSelect(): MockInterface
{
    $connection = fakeConnection();
    $connection->shouldReceive('select')->andReturn([]);
    $connection->shouldReceive('getTablePrefix')->andReturn('');

    return $connection;
}

it('executes allowed read-only queries', function (): void {
    expectSelect();
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
    fakeConnection()->shouldReceive('select')->never();

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
    fakeConnection()->shouldReceive('select')->never();

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
            ->toolTextContains('Please pass a valid SQL query');
    }
});

it('allows queries starting with any allowed keyword even when identifiers look like SQL keywords', function (): void {
    expectSelect();

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
    $connection = fakeConnection();
    $connection->shouldReceive('getTablePrefix')->andReturn('wp_');

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
        'SELECT * FROM users ORDER BY name DESC' => 'SELECT * FROM wp_users ORDER BY name DESC',
        'SELECT * FROM users ORDER BY name DESC LIMIT 10' => 'SELECT * FROM wp_users ORDER BY name DESC LIMIT 10',
        'SELECT * FROM users ORDER BY name DESC OFFSET 5' => 'SELECT * FROM wp_users ORDER BY name DESC OFFSET 5',
        'SELECT * FROM users ORDER BY created_at DESC, name ASC' => 'SELECT * FROM wp_users ORDER BY created_at DESC, name ASC',
        'SELECT * FROM public.users' => 'SELECT * FROM public.wp_users',
        'SELECT * FROM public.users JOIN public.posts ON public.users.id = public.posts.user_id' => 'SELECT * FROM public.wp_users JOIN public.wp_posts ON public.users.id = public.posts.user_id',
        'SELECT * FROM "public"."users"' => 'SELECT * FROM "public"."wp_users"',
        'SELECT * FROM `mydb`.`users`' => 'SELECT * FROM `mydb`.`wp_users`',
    ];

    foreach ($testCases as $input => $expected) {
        $connection->shouldReceive('select')->with($expected)->once()->andReturn([]);

        $tool = new DatabaseQuery;
        $response = $tool->handle(new Request(['query' => $input]));

        expect($response)->isToolResult()->toolHasNoError();
    }
});

test('it runs a successful lowercase select query', function (): void {
    expectSelect();

    $tool = new DatabaseQuery;
    $response = $tool->handle(new Request(['query' => 'select * from examples']));

    expect($response)->isToolResult()->toolHasNoError();
});

test('it runs a successful with … select query', function (): void {
    expectSelect();

    $tool = new DatabaseQuery;
    $response = $tool->handle(new Request([
        'query' => 'WITH cte AS (SELECT * FROM examples) SELECT * FROM cte',
    ]));

    expect($response)->isToolResult()->toolHasNoError();
});

test('it rejects an insert query', function (): void {
    expectSelect();
    $tool = new DatabaseQuery;

    $response = $tool->handle(new Request([
        'query' => "INSERT INTO examples (name) VALUES ('Otwell')",
    ]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Only read-only queries are allowed (SELECT, SHOW, EXPLAIN, DESCRIBE, DESC, WITH … SELECT).');
});

test('it rejects a with … write query', function (): void {
    expectSelect();
    $tool = new DatabaseQuery;

    $response = $tool->handle(new Request([
        'query' => 'WITH data AS (VALUES (1)) DELETE FROM examples',
    ]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Only read-only queries are allowed (SELECT, SHOW, EXPLAIN, DESCRIBE, DESC, WITH … SELECT).');
});

test('it reports a failure when the database call throws', function (): void {
    $connection = fakeConnection();
    $connection->shouldReceive('getTablePrefix')->andReturn('');
    $connection->shouldReceive('select')
        ->once()
        ->andThrow(new RuntimeException('Simulated DB failure'));

    $tool = new DatabaseQuery;
    $response = $tool->handle(new Request(['query' => 'SELECT * FROM examples']));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Query failed: Simulated DB failure');
});

/**
 * Point DB::connection() at a mocked MongoDB connection so the tool takes the MQL path.
 * Validation-error tests never reach the driver, so getDatabase() may go unused.
 */
function fakeMongoConnection(array $documents = []): MongoDBConnection
{
    // Mockery can't mock CursorInterface on Mockery 1.6.x / PHP 8.4 (its Iterator::current(): mixed
    // clashes with CursorInterface::current(): object|array|null), so use a concrete double.
    $cursor = new class($documents) implements CursorInterface
    {
        public function __construct(private array $documents) {}

        public function toArray(): array
        {
            return $this->documents;
        }

        public function current(): array|object|null
        {
            return current($this->documents) ?: null;
        }

        public function key(): ?int
        {
            return key($this->documents);
        }

        public function next(): void
        {
            next($this->documents);
        }

        public function valid(): bool
        {
            return key($this->documents) !== null;
        }

        public function rewind(): void
        {
            reset($this->documents);
        }

        public function getId(): Int64
        {
            throw new LogicException('unused in tests');
        }

        public function getServer(): Server
        {
            throw new LogicException('unused in tests');
        }

        public function isDead(): bool
        {
            return true;
        }

        public function setTypeMap(array $typemap): void {}
    };

    $database = Mockery::mock(Database::class);
    $database->shouldReceive('command')->andReturn($cursor);

    $connection = Mockery::mock(MongoDBConnection::class);
    $connection->shouldReceive('getDatabase')->andReturn($database);

    DB::shouldReceive('connection')->with(null)->andReturn($connection);

    return $connection;
}

test('it runs a successful mql find command', function (): void {
    fakeMongoConnection([
        ['_id' => 1, 'name' => 'Taylor'],
    ]);

    $tool = new DatabaseQuery;
    $response = $tool->handle(new Request([
        'command' => ['find' => 'examples', 'filter' => ['name' => 'Taylor']],
    ]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $rows): void {
            expect($rows)->toHaveCount(1)
                ->and($rows[0]['name'])->toBe('Taylor');
        });
});

test('it runs a successful read-only aggregation', function (array $pipeline): void {
    fakeMongoConnection([['count' => 1]]);

    $tool = new DatabaseQuery;
    $response = $tool->handle(new Request([
        'command' => ['aggregate' => 'examples', 'pipeline' => $pipeline],
    ]));

    expect($response)->isToolResult()->toolHasNoError();
})->with([
    'flat stages' => [[
        ['$match' => ['name' => 'Taylor']],
        ['$sort' => ['name' => 1]],
    ]],
    'nested read-only lookup' => [[
        ['$lookup' => ['from' => 'others', 'pipeline' => [['$match' => ['ok' => true]]]]],
    ]],
    'nested read-only unionWith' => [[
        ['$unionWith' => ['coll' => 'others', 'pipeline' => [['$match' => ['ok' => true]]]]],
    ]],
    'nested read-only facet' => [[
        ['$facet' => [
            'a' => [['$match' => ['ok' => true]]],
            'b' => [['$count' => 'total']],
        ]],
    ]],
    'deeply nested read-only pipelines' => [[
        ['$lookup' => ['from' => 'others', 'pipeline' => [
            ['$unionWith' => ['coll' => 'more', 'pipeline' => [['$match' => ['ok' => true]]]]],
        ]]],
    ]],
]);

test('it rejects an empty mql command', function (): void {
    fakeMongoConnection();

    $tool = new DatabaseQuery;
    $response = $tool->handle(new Request(['command' => []]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Please pass a valid MongoDB command');
});

test('it rejects a write mql command', function (): void {
    fakeMongoConnection();

    $tool = new DatabaseQuery;
    $response = $tool->handle(new Request([
        'command' => ['insert' => 'examples', 'documents' => [['name' => 'Otwell']]],
    ]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Only read commands are allowed (aggregate, count, distinct, find).');
});

test('it rejects a write stage nested in an aggregation', function (array $pipeline, string $writeStage): void {
    fakeMongoConnection();

    $tool = new DatabaseQuery;
    $response = $tool->handle(new Request([
        'command' => ['aggregate' => 'examples', 'pipeline' => $pipeline],
    ]));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains(sprintf('Only read aggregation stages are allowed. Found: %s', $writeStage));
})->with([
    'write nested in lookup' => [
        [['$lookup' => ['from' => 'others', 'pipeline' => [
            ['$match' => ['name' => 'Taylor']],
            ['$merge' => 'evil'],
        ]]]],
        'merge',
    ],
    'write nested in unionWith' => [
        [['$unionWith' => ['coll' => 'others', 'pipeline' => [
            ['$out' => 'evil'],
        ]]]],
        'out',
    ],
    'write nested in facet' => [
        [['$facet' => [
            'a' => [['$match' => ['ok' => true]]],
            'b' => [['$merge' => 'evil']],
        ]]],
        'merge',
    ],
    'write nested three levels deep' => [
        [['$lookup' => ['from' => 'a', 'pipeline' => [
            ['$unionWith' => ['coll' => 'b', 'pipeline' => [
                ['$out' => 'evil'],
            ]]],
        ]]]],
        'out',
    ],
]);
