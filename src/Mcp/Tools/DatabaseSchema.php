<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Boost\Mcp\Tools\DatabaseSchema\SchemaDriverFactory;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class DatabaseSchema extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Read the database schema for this application. Returns table names, columns, data types, indexes, and foreign keys. Use "filter" to narrow down tables by name (substring match).';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'database' => $schema->string()
                ->description("Name of the database connection to dump (defaults to app's default connection, often not needed)"),
            'filter' => $schema->string()
                ->description('Filter tables by name (substring match).'),
            'include_views' => $schema->boolean()
                ->description('Include database views. Defaults to false.'),
            'include_routines' => $schema->boolean()
                ->description('Include stored procedures, functions, and sequences. Defaults to false.'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $connection = $request->get('database') ?? config('database.default');
        $filter = $request->get('filter') ?? '';
        $includeViews = $request->get('include_views', false);
        $includeRoutines = $request->get('include_routines', false);

        $cacheKey = sprintf(
            'boost:mcp:database-schema:%s:%s:%d:%d',
            $connection,
            $filter,
            (int) $includeViews,
            (int) $includeRoutines
        );

        $schema = rescue(
            fn () => Cache::remember($cacheKey, 20, fn (): array => $this->getDatabaseStructure(
                $connection,
                $filter,
                $includeViews,
                $includeRoutines
            )),
            fn (): array => $this->getDatabaseStructure($connection, $filter, $includeViews, $includeRoutines),
            report: false
        );

        return Response::json($schema);
    }

    /**
     * @return array{engine: string, tables: array<string, mixed>, views?: array<mixed>, routines?: array{stored_procedures: array<mixed>, functions: array<mixed>, sequences: array<mixed>}}
     */
    protected function getDatabaseStructure(
        ?string $connection,
        string $filter = '',
        bool $includeViews = false,
        bool $includeRoutines = false
    ): array {
        $driver = SchemaDriverFactory::make($connection);

        $result = [
            'engine' => DB::connection($connection)->getDriverName(),
            'tables' => $this->getAllTablesStructure($connection, $filter),
        ];

        if ($includeViews) {
            $result['views'] = $driver->getViews();
        }

        if ($includeRoutines) {
            $result['routines'] = [
                'stored_procedures' => $driver->getStoredProcedures(),
                'functions' => $driver->getFunctions(),
                'sequences' => $driver->getSequences(),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getAllTablesStructure(?string $connection, string $filter = ''): array
    {
        $structures = [];

        foreach ($this->getAllTables($connection) as $table) {
            $tableName = is_object($table) ? $table->name : ($table['name'] ?? '');

            if ($filter !== '' && ! str_contains(strtolower($tableName), strtolower($filter))) {
                continue;
            }

            $structures[$tableName] = $this->getTableStructure($connection, $tableName);
        }

        return $structures;
    }

    /**
     * @return array<int, object|array<string, mixed>>
     */
    protected function getAllTables(?string $connection): array
    {
        return SchemaDriverFactory::make($connection)->getTables();
    }

    protected function getTableStructure(?string $connection, string $tableName): array
    {
        $driver = SchemaDriverFactory::make($connection);

        try {
            $columns = $this->getTableColumns($connection, $tableName);
            $indexes = $this->getTableIndexes($connection, $tableName);
            $foreignKeys = $this->getTableForeignKeys($connection, $tableName);
            $triggers = $driver->getTriggers($tableName);
            $checkConstraints = $driver->getCheckConstraints($tableName);

            return [
                'columns' => $columns,
                'indexes' => $indexes,
                'foreign_keys' => $foreignKeys,
                'triggers' => $triggers,
                'check_constraints' => $checkConstraints,
            ];
        } catch (Exception $exception) {
            Log::error('Failed to get table structure for: '.$tableName, [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'error' => 'Failed to get structure: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, array{type: string}>
     */
    protected function getTableColumns(?string $connection, string $tableName): array
    {
        $schema = Schema::connection($connection);
        $columnDetails = [];

        foreach ($schema->getColumnListing($tableName) as $column) {
            $columnDetails[$column] = ['type' => $schema->getColumnType($tableName, $column)];
        }

        return $columnDetails;
    }

    /**
     * @return array<string, array{columns: mixed, type: mixed, is_unique: bool, is_primary: bool}>
     */
    protected function getTableIndexes(?string $connection, string $tableName): array
    {
        try {
            $indexDetails = [];

            foreach (Schema::connection($connection)->getIndexes($tableName) as $index) {
                $indexDetails[$index['name']] = [
                    'columns' => Arr::get($index, 'columns'),
                    'type' => Arr::get($index, 'type'),
                    'is_unique' => Arr::get($index, 'unique', false),
                    'is_primary' => Arr::get($index, 'primary', false),
                ];
            }

            return $indexDetails;
        } catch (Exception) {
            return [];
        }
    }

    protected function getTableForeignKeys(?string $connection, string $tableName): array
    {
        try {
            return Schema::connection($connection)->getForeignKeys($tableName);
        } catch (Exception) {
            return [];
        }
    }
}
