<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools\DatabaseSchema;

use Exception;
use Illuminate\Support\Facades\DB;

class SQLiteSchemaDriver extends DatabaseSchemaDriver
{
    public function getViews(): array
    {
        try {
            return DB::connection($this->connection)->select("
                SELECT name, sql
                FROM sqlite_master
                WHERE type = 'view'
            ");
        } catch (Exception) {
            return [];
        }
    }

    public function getStoredProcedures(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [];
    }

    public function getTriggers(?string $table = null): array
    {
        try {
            $sql = "SELECT name, sql FROM sqlite_master WHERE type = 'trigger'";
            if ($this->hasTable($table)) {
                $sql .= ' AND tbl_name = ?';

                return DB::connection($this->connection)->select($sql, [$table]);
            }

            return DB::connection($this->connection)->select($sql);
        } catch (Exception) {
            return [];
        }
    }

    public function getCheckConstraints(string $table): array
    {
        return [];
    }

    public function getSequences(): array
    {
        return [];
    }
}
