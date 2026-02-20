<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

#[IsReadOnly]
class DatabaseQuery extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Execute a read-only SQL query against the configured database.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The SQL query to execute. Only read-only queries are allowed (i.e. SELECT, SHOW, EXPLAIN, DESCRIBE).')
                ->required(),
            'database' => $schema->string()
                ->description("Optional database connection name to use. Defaults to the application's default connection."),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $query = trim((string) $request->string('query'));
        $token = strtok(ltrim($query), " \t\n\r");

        if (! $token) {
            return Response::error('Please pass a valid query');
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
            return Response::error('Only read-only queries are allowed (SELECT, SHOW, EXPLAIN, DESCRIBE, DESC, WITH … SELECT).');
        }

        $connectionName = $request->get('database');

        try {
            $connection = DB::connection($connectionName);
            $prefix = $connection->getTablePrefix();

            if ($prefix) {
                $query = $this->addPrefixToQuery($query, $prefix);
            }

            return Response::json(
                $connection->select($query)
            );
        } catch (Throwable $throwable) {
            return Response::error('Query failed: '.$throwable->getMessage());
        }
    }

    protected function addPrefixToQuery(string $query, string $prefix): string
    {
        $cteNames = $this->extractCteNames($query);

        $pattern = '/\b(FROM|JOIN|INTO|UPDATE|TABLE|DESCRIBE|DESC)\s+([`"\']?)(\w+)\2/i';

        return preg_replace_callback($pattern, function (array $matches) use ($prefix, $cteNames): string {
            $keyword = $matches[1];
            $quote = $matches[2];
            $tableName = $matches[3];

            if (str_starts_with($tableName, $prefix) || in_array($tableName, $cteNames, true)) {
                return $matches[0];
            }

            return "{$keyword} {$quote}{$prefix}{$tableName}{$quote}";
        }, $query) ?? $query;
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
}
