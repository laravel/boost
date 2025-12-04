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
        if ($firstWord === 'WITH' && ! preg_match('/with\s+.*select\b/i', $query)) {
            $isReadOnly = false;
        }

        if (! $isReadOnly) {
            return Response::error('Only read-only queries are allowed (SELECT, SHOW, EXPLAIN, DESCRIBE, DESC, WITH … SELECT).');
        }

        $connectionName = $request->get('database');

        try {
            return Response::json(
                DB::connection($connectionName)->select($query)
            );
        } catch (Throwable $throwable) {
            return Response::error('Query failed: '.$throwable->getMessage());
        }
    }
}
