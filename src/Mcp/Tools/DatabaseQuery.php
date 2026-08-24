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
     * Statement-starting keywords that write data.
     */
    private const WRITE_KEYWORDS = 'DELETE|UPDATE|DROP|ALTER|TRUNCATE|RENAME|CREATE|MERGE';

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
                $this->runSelectViaReadOnlyTransaction($connection, $query)
            );
        } catch (Throwable $throwable) {
            return Response::error('Query failed: '.$throwable->getMessage());
        }
    }

    /**
     * Run the query inside a database-enforced read-only transaction.
     *
     * The keyword filter above is a fast-fail guard, not the source of truth: SQL dialects have
     * too many shapes (data-modifying CTEs, INTO OUTFILE, vendor extensions) for lexical parsing
     * to ever be exhaustive. This wraps execution in a transaction the engine itself is asked to
     * treat as read-only, and always rolls it back so nothing persists even if that request is
     * ignored by the driver.
     */
    protected function runSelectViaReadOnlyTransaction($connection, string $query): array
    {
        $driver = $connection->getDriverName();

        // MySQL/MariaDB only honor READ ONLY when set before the transaction starts.
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement('SET TRANSACTION READ ONLY');
        }

        $connection->beginTransaction();

        try {
            if ($driver === 'pgsql') {
                $connection->statement('SET TRANSACTION READ ONLY');
            } elseif ($driver === 'sqlite') {
                $connection->statement('PRAGMA query_only = ON');
            }

            return $connection->select($query);
        } finally {
            $connection->rollBack();

            if ($driver === 'sqlite') {
                $connection->statement('PRAGMA query_only = OFF');
            }
        }
    }

    /**
     * Determine if the given query only reads data.
     */
    protected function isReadOnlyQuery(string $query): bool
    {
        ['structure' => $structure, 'hasVersionComment' => $hasVersionComment] = $this->withoutLiteralsAndComments($query);

        // MySQL executes version-gated "/*! ... */" comments, so they cannot be treated as comments.
        if ($hasVersionComment) {
            return false;
        }

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

        // Blocks write keywords at every statement boundary (^, after (, ), or ;); REPLACE(...)/INSERT(...) function calls stay allowed.
        if (preg_match('/(^|[();])\s*(?:(?:'.self::WRITE_KEYWORDS.')\b|(?:INSERT|REPLACE)\b(?!\s*\())/i', $structure)) {
            return false;
        }

        // EXPLAIN ANALYZE executes the statement it explains, so EXPLAIN may only target reads.
        if ($firstWord === 'EXPLAIN' && preg_match('/^\s*EXPLAIN\s+(?:\([^)]*\)\s*|(?:ANALYZE|VERBOSE|QUERY\s+PLAN|FORMAT\s*=?\s*\w+)\s+)*(?:'.self::WRITE_KEYWORDS.'|INSERT|REPLACE)\b/i', $structure)) {
            return false;
        }

        // INTO always writes: SELECT … INTO, INTO OUTFILE, and INTO DUMPFILE.
        if (preg_match('/\bINTO\b/i', $structure)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{structure: string, hasVersionComment: bool}
     */
    protected function withoutLiteralsAndComments(string $query): array
    {
        $structure = '';
        $state = 'none';
        $hasVersionComment = false;
        $length = strlen($query);

        for ($i = 0; $i < $length; $i++) {
            $char = $query[$i];
            $next = $query[$i + 1] ?? '';

            if ($state === 'none') {
                if ($char === "'" || $char === '"' || $char === '`') {
                    $state = match ($char) {
                        "'" => 'single',
                        '"' => 'double',
                        default => 'backtick',
                    };
                    $structure .= ' ';
                } elseif ($char === '-' && $next === '-') {
                    $state = 'line_comment';
                    $structure .= ' ';
                    $i++;
                } elseif ($char === '/' && $next === '*') {
                    if (($query[$i + 2] ?? '') === '!') {
                        // MySQL executes version-gated "/*! ... */" comments, so they cannot be treated as comments.
                        $hasVersionComment = true;
                        $structure .= $char;
                    } else {
                        $state = 'block_comment';
                        $structure .= ' ';
                        $i++;
                    }
                } else {
                    $structure .= $char;
                }
            } elseif ($state === 'line_comment') {
                if ($char === "\n") {
                    $state = 'none';
                    $structure .= $char;
                }
            } elseif ($state === 'block_comment') {
                if ($char === '*' && $next === '/') {
                    $state = 'none';
                    $i++;
                }
            } else {
                $quote = match ($state) {
                    'single' => "'",
                    'double' => '"',
                    default => '`',
                };

                if ($char === $quote) {
                    if ($next === $quote) {
                        $i++; // doubled quote escapes itself; literal continues
                    } else {
                        $state = 'none';
                    }
                }
            }
        }

        return ['structure' => $structure, 'hasVersionComment' => $hasVersionComment];
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
