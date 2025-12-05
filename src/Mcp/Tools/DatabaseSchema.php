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
    protected string $description = 'Read the database schema for this application, including table names, columns, data types, indexes, foreign keys, and more.';

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
                ->description('Filter the tables by name'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $connection = $request->get('database') ?? config('database.default');
        $filter = $request->get('filter') ?? '';
        $cacheKey = "boost:mcp:database-schema:{$connection}:{$filter}";

        $schema = Cache::remember($cacheKey, 20, fn (): array => $this->getDatabaseStructure($connection, $filter));

        return Response::json($schema);
    }

    protected function getDatabaseStructure(?string $connection, string $filter = ''): array
    {
        return [
            'engine' => DB::connection($connection)->getDriverName(),
            'tables' => $this->getAllTablesStructure($connection, $filter),
            'global' => $this->getGlobalStructure($connection),
        ];
    }

    protected function getAllTablesStructure(?string $connection, string $filter = ''): array
    {
        $structures = [];

        foreach ($this->getAllTables($connection) as $table) {
            $tableName = $table['name'];

            if ($filter && ! str_contains(strtolower((string) $tableName), strtolower($filter))) {
                continue;
            }

            $structures[$tableName] = $this->getTableStructure($connection, $tableName);
        }

        return $structures;
    }

    protected function getAllTables(?string $connection): array
    {
        return Schema::connection($connection)->getTables();
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

    protected function getTableColumns(?string $connection, string $tableName): array
    {
        $columns = Schema::connection($connection)->getColumnListing($tableName);
        $columnDetails = [];

        foreach ($columns as $column) {
            $columnDetails[$column] = [
                'type' => Schema::connection($connection)->getColumnType($tableName, $column),
            ];
        }

        return $columnDetails;
    }

    protected function getTableIndexes(?string $connection, string $tableName): array
    {
        try {
            $indexes = Schema::connection($connection)->getIndexes($tableName);
            $indexDetails = [];

            foreach ($indexes as $index) {
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

    protected function getGlobalStructure(?string $connection): array
    {
        $driver = SchemaDriverFactory::make($connection);

        return [
            'views' => $driver->getViews(),
            'stored_procedures' => $driver->getStoredProcedures(),
            'functions' => $driver->getFunctions(),
            'sequences' => $driver->getSequences(),
        ];
    }
}
