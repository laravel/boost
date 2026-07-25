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

        if ($query === '') {
            return Response::error('Please pass a valid query');
        }

        if (! $this->isReadOnlyQuery($query)) {
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

    /**
     * Determine if the given query only reads data.
     */
    protected function isReadOnlyQuery(string $query): bool
    {
        // MySQL executes version-gated "/*! ... */" comments, so they cannot be treated as comments.
        if (str_contains($query, '/*!')) {
            return false;
        }

        $structure = $this->withoutLiteralsAndComments($query);

        // Reject stacked statements, but allow a single trailing semicolon.
        if (preg_match('/;\s*\S/', $structure)) {
            return false;
        }

        $token = strtok($structure, " \t\n\r");

        if ($token === false) {
            return false;
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

        if (! in_array($firstWord, $allowList, true)) {
            return false;
        }

        // Additional validation for WITH … SELECT.
        if ($firstWord === 'WITH' && ! preg_match('/\)\s*SELECT\b/i', $structure)) {
            return false;
        }

        // Block write statements wherever a statement can start: at the beginning, after an
        // opening parenthesis (data-modifying CTE bodies), after a closing parenthesis
        // (statements following CTEs), or after a semicolon. INSERT and REPLACE also exist
        // as string functions, so their function-call form remains allowed.
        if (preg_match('/(^|[();])\s*(?:(?:DELETE|UPDATE|DROP|ALTER|TRUNCATE|RENAME|CREATE|MERGE)\b|(?:INSERT|REPLACE)\b(?!\s*\())/i', $structure)) {
            return false;
        }

        // EXPLAIN ANALYZE executes the statement it explains, so EXPLAIN may only target reads.
        if ($firstWord === 'EXPLAIN' && preg_match('/^\s*EXPLAIN\s+(?:\([^)]*\)\s*|(?:ANALYZE|VERBOSE|QUERY\s+PLAN|FORMAT\s*=?\s*\w+)\s+)*(?:DELETE|UPDATE|INSERT|REPLACE|MERGE|DROP|ALTER|TRUNCATE|RENAME|CREATE)\b/i', $structure)) {
            return false;
        }

        // INTO always writes: SELECT … INTO, INTO OUTFILE, and INTO DUMPFILE.
        if (preg_match('/\bINTO\b/i', $structure)) {
            return false;
        }

        return true;
    }

    /**
     * Strip string literals, quoted identifiers, and comments so keyword
     * checks only run against the structural parts of the query.
     */
    protected function withoutLiteralsAndComments(string $query): string
    {
        $patterns = [
            "/'(?:[^'\\\\]|\\\\.|'')*'/s",  // single-quoted strings
            '/"(?:[^"\\\\]|\\\\.|"")*"/s',  // double-quoted strings and identifiers
            '/`(?:[^`]|``)*`/s',            // backtick-quoted identifiers
            '/--[^\n]*/',                   // line comments
            '#/\*.*?\*/#s',                 // block comments
        ];

        return preg_replace($patterns, ' ', $query) ?? $query;
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
}
