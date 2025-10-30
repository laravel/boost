<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools\DatabaseSchema;

use Exception;
use Illuminate\Support\Facades\DB;

class MySQLSchemaDriver extends DatabaseSchemaDriver
{
    public function getViews(): array
    {
        try {
            return DB::connection($this->connection)->select('
                SELECT TABLE_NAME as name, VIEW_DEFINITION as definition
                FROM information_schema.VIEWS
                WHERE TABLE_SCHEMA = DATABASE()
            ');
        } catch (Exception) {
            return [];
        }
    }

    public function getStoredProcedures(): array
    {
        try {
            return DB::connection($this->connection)->select('SHOW PROCEDURE STATUS WHERE Db = DATABASE()');
        } catch (Exception) {
            return [];
        }
    }

    public function getFunctions(): array
    {
        try {
            return DB::connection($this->connection)->select('SHOW FUNCTION STATUS WHERE Db = DATABASE()');
        } catch (Exception) {
            return [];
        }
    }

    public function getTriggers(?string $table = null): array
    {
        try {
            if ($this->hasTable($table)) {
                return DB::connection($this->connection)->select('SHOW TRIGGERS WHERE `Table` = ?', [$table]);
            }

            return DB::connection($this->connection)->select('SHOW TRIGGERS');
        } catch (Exception) {
            return [];
        }
    }

    public function getCheckConstraints(string $table): array
    {
        try {
            return DB::connection($this->connection)->select('
                SELECT CONSTRAINT_NAME, CHECK_CLAUSE
                FROM information_schema.CHECK_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
            ', [$table]);
        } catch (Exception) {
            return [];
        }
    }

    public function getSequences(): array
    {
        return [];
    }
}
