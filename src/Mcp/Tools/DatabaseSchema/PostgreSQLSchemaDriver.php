<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools\DatabaseSchema;

use Exception;
use Illuminate\Support\Facades\DB;

class PostgreSQLSchemaDriver extends DatabaseSchemaDriver
{
    public function getViews(): array
    {
        try {
            return DB::connection($this->connection)->select("
                SELECT schemaname, viewname, definition
                FROM pg_views
                WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
            ");
        } catch (Exception) {
            return [];
        }
    }

    public function getStoredProcedures(): array
    {
        try {
            return DB::connection($this->connection)->select("
                SELECT proname, prosrc, proargnames, prorettype
                FROM pg_proc p
                JOIN pg_namespace n ON p.pronamespace = n.oid
                WHERE n.nspname NOT IN ('pg_catalog', 'information_schema')
                AND prokind = 'p'
            ");
        } catch (Exception) {
            return [];
        }
    }

    public function getFunctions(): array
    {
        try {
            return DB::connection($this->connection)->select("
                SELECT proname, prosrc, proargnames, prorettype
                FROM pg_proc p
                JOIN pg_namespace n ON p.pronamespace = n.oid
                WHERE n.nspname NOT IN ('pg_catalog', 'information_schema')
                AND prokind = 'f'
            ");
        } catch (Exception) {
            return [];
        }
    }

    public function getTriggers(?string $table = null): array
    {
        try {
            $sql = '
                SELECT trigger_name, event_manipulation, event_object_table, action_statement
                FROM information_schema.triggers
                WHERE trigger_schema = current_schema()
            ';
            if ($this->hasTable($table)) {
                $sql .= ' AND event_object_table = ?';

                return DB::connection($this->connection)->select($sql, [$table]);
            }

            return DB::connection($this->connection)->select($sql);
        } catch (Exception) {
            return [];
        }
    }

    public function getCheckConstraints(string $table): array
    {
        try {
            return DB::connection($this->connection)->select("
                SELECT conname, pg_get_constraintdef(oid) as definition
                FROM pg_constraint
                WHERE contype = 'c'
                AND conrelid = ?::regclass
            ", [$table]);
        } catch (Exception) {
            return [];
        }
    }

    public function getSequences(): array
    {
        try {
            return DB::connection($this->connection)->select('
                SELECT sequence_name, start_value, minimum_value, maximum_value, increment
                FROM information_schema.sequences
                WHERE sequence_schema = current_schema()
            ');
        } catch (Exception) {
            return [];
        }
    }
}
