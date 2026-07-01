<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;
use MongoDB\Laravel\Connection as MongoDBConnection;

#[IsReadOnly]
class DatabaseQuery extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Execute a read-only query against the configured database. Default to an SQL query. If the database driver is `mongodb`, use MQL commands.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The SQL query to execute. Only read-only operations are allowed (i.e. SELECT, SHOW, EXPLAIN, DESCRIBE)'),
            'command' => $schema->object()
                ->description('The MQL command to execute for MongoDB connections. Only read commands are allowed (i.e. aggregate, count, distinct, find)'),
            'database' => $schema->string()
                ->description("Optional database connection name to use. Defaults to the application's default connection."),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $connection = DB::connection($request->get('database'));

        try {
            $result = class_exists(MongoDBConnection::class) && $connection instanceof MongoDBConnection
                ? $this->handleMql($request->array('command'), $connection)
                : $this->handleSql($request->string('query'), $connection);

            return Response::json($result);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        } catch (Throwable $e) {
            return Response::error('Query failed: ' . $e->getMessage());
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function handleSql(\Stringable $query, ConnectionInterface $connection): array
    {
        $query = trim((string) $query);
        $token = strtok(ltrim($query), " \t\n\r");

        if (! $token) {
            throw new InvalidArgumentException('Please pass a valid SQL query');
        }

        $firstWord = strtoupper($token);

        // Allowed read-only commands.
        $allowList = [
            'SELECT',
            'SHOW',
            'EXPLAIN',
            'DESCRIBE',
            'DESC',
            'WITH',        // SELECT must follow Common-table expressions
            'VALUES',      // Returns literal values
            'TABLE',       // PostgresSQL shorthand for SELECT *
        ];

        $isReadOnly = in_array($firstWord, $allowList, true);

        // Additional validation for WITH … SELECT.
        if ($firstWord === 'WITH') {
            if (! preg_match('/\)\s*SELECT\b/i', $query)) {
                $isReadOnly = false;
            }

            if (preg_match('/\)\s*(DELETE|UPDATE|INSERT|DROP|ALTER|TRUNCATE|REPLACE|RENAME|CREATE)\b/i', $query)) {
                $isReadOnly = false;
            }
        }

        if (! $isReadOnly) {
            throw new InvalidArgumentException('Only read-only queries are allowed (SELECT, SHOW, EXPLAIN, DESCRIBE, DESC, WITH … SELECT).');
        }

        $prefix = $connection->getTablePrefix();

        if ($prefix) {
            $query = $this->addPrefixToQuery($query, $prefix);
        }

        return $connection->select($query);
    }

    protected function addPrefixToQuery(string $query, string $prefix): string
    {
        $cteNames = $this->extractCteNames($query);

        // Anchored to the start so the `ORDER BY ... DESC` sort direction is never matched.
        $describePattern = '/^(\s*)(DESCRIBE|DESC)\s+((?:[`"]?\w+[`"]?\s*\.\s*)?)([`"\']?)(\w+)\4/i';

        $query = preg_replace_callback($describePattern, function (array $matches) use ($prefix, $cteNames): string {
            [$full, $leading, $keyword, $qualifier, $quote, $tableName] = $matches;

            if ($this->tableIsPrefixedOrCte($tableName, $prefix, $cteNames)) {
                return $full;
            }

            return "{$leading}{$keyword} {$qualifier}{$quote}{$prefix}{$tableName}{$quote}";
        }, $query) ?? $query;

        $pattern = '/\b(FROM|JOIN|INTO|UPDATE|TABLE)\s+((?:[`"]?\w+[`"]?\s*\.\s*)?)([`"\']?)(\w+)\3/i';

        return preg_replace_callback($pattern, function (array $matches) use ($prefix, $cteNames): string {
            [$full, $keyword, $qualifier, $quote, $tableName] = $matches;

            if ($this->tableIsPrefixedOrCte($tableName, $prefix, $cteNames)) {
                return $full;
            }

            return "{$keyword} {$qualifier}{$quote}{$prefix}{$tableName}{$quote}";
        }, $query) ?? $query;
    }

    /**
     * @param  array<int, string>  $cteNames
     */
    protected function tableIsPrefixedOrCte(string $tableName, string $prefix, array $cteNames): bool
    {
        return str_starts_with($tableName, $prefix) || in_array($tableName, $cteNames, true);
    }

    /**
     * Extract CTE (Common Table Expression) names from a query.
     *
     * @return array<int, string>
     */
    protected function extractCteNames(string $query): array
    {
        if (preg_match_all('/\b(\w+)\s*(?:\([^)]*\))?\s*AS\s*\(/i', $query, $matches)) {
            return $matches[1];
        }

        return [];
    }

    /**
     * @param array<string,mixed> $command
     *
     * @throws InvalidArgumentException
     */
    private function handleMql(array $command, MongoDBConnection $connection): array
    {
        if ($command === []) {
            throw new InvalidArgumentException('Please pass a valid MongoDB command');
        }

        // Allowed CRUD commands (https://www.mongodb.com/docs/manual/reference/mql/crud-commands/)
        $allowList = ['aggregate', 'count', 'distinct', 'find'];
        $operation = array_key_first($command);

        if (! in_array($operation, $allowList, true)) {
            throw new InvalidArgumentException(sprintf('Only read commands are allowed (%s).', join(', ', $allowList)));
        }

        if ($operation === 'aggregate') {
            // Check nested write ops recursively with conservative allow list
            $this->ensureNoNestedWriteInAggregation($command['pipeline'] ?? []);
        }

        $cursor = $connection->getDatabase()->command($command);

        return $cursor->toArray();
    }

    // BSON objects are capped at 100 nesting levels:
    // https://www.mongodb.com/docs/manual/reference/limits/#mongodb-limit-Nested-Depth-for-BSON-Documents
    private const MAX_NESTING_LEVEL = 100;

    // Aggregation pipelines are capped at 1000 stages
    // https://www.mongodb.com/docs/manual/core/aggregation-pipeline-limits/#number-of-stages-restrictions
    private const MAX_STAGES_PER_PIPELINE = 1000;

    /**
     * @throws InvalidArgumentException
     */
    private function ensureNoNestedWriteInAggregation(array $pipeline, int $level = 1): void
    {
        if ($level > self::MAX_NESTING_LEVEL) {
            throw new InvalidArgumentException(sprintf('Aggregation nesting exceeds the maximum of %d levels.', self::MAX_NESTING_LEVEL));
        }

        if (count($pipeline) > self::MAX_STAGES_PER_PIPELINE) {
            throw new InvalidArgumentException(sprintf('A pipeline exceeds the maximum of %d stages.', self::MAX_STAGES_PER_PIPELINE));
        }

        // These aggregation stages may contain nested aggregation pipelines
        // and must be checked for write ops recursively.
        $supportsNestedWrites = ["facet", "lookup", "unionWith"];

        $allowList = array_merge($supportsNestedWrites, [
            "addFields",
            "bucket",
            "bucketAuto",
            "count",
            "densify",
            "fill",
            "graphLookup",
            "group",
            "limit",
            "match",
            "project",
            "redact",
            "replaceRoot",
            "replaceWith",
            "sample",
            "set",
            "setWindowFields",
            "skip",
            "sort",
            "sortByCount",
            "unwind",
            "unset"
        ]);

        // A pipeline is a list of single-key stage documents, e.g. [['$match' => [...]], ...].
        foreach ($pipeline as $stage) {
            $operator = array_key_first($stage);
            $stageName = str_replace('$', '', (string) $operator);
            $stageBody = $stage[$operator];

            if (! in_array($stageName, $allowList, true)) {
                throw new InvalidArgumentException(sprintf('Only read aggregation stages are allowed. Found: %s', $stageName));
            }

            if (! in_array($stageName, $supportsNestedWrites, true)) {
                continue;
            }

            switch ($stageName) {
                case 'facet':
                    array_walk($stageBody, fn ($pipeline) => $this->ensureNoNestedWriteInAggregation($pipeline, $level + 1));
                    break;
                case 'lookup':
                case 'unionWith':
                    // Both stages take an optional single sub-pipeline; unionWith may also be a plain collection name.
                    if (is_array($stageBody) && isset($stageBody['pipeline'])) {
                        $this->ensureNoNestedWriteInAggregation($stageBody['pipeline'], $level + 1);
                    }
                    break;
            }
        }
    }
}
