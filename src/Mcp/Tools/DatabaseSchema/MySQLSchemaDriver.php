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
            // MySQL's CHECK_CONSTRAINTS table has no TABLE_NAME column (MariaDB's does),
            // so the table mapping must come from TABLE_CONSTRAINTS on both engines.
            return DB::connection($this->connection)->select('
                SELECT cc.CONSTRAINT_NAME, cc.CHECK_CLAUSE
                FROM information_schema.CHECK_CONSTRAINTS cc
                JOIN information_schema.TABLE_CONSTRAINTS tc
                    ON tc.CONSTRAINT_SCHEMA = cc.CONSTRAINT_SCHEMA
                    AND tc.CONSTRAINT_NAME = cc.CONSTRAINT_NAME
                WHERE cc.CONSTRAINT_SCHEMA = DATABASE()
                AND tc.TABLE_NAME = ?
                AND tc.CONSTRAINT_TYPE = ?
            ', [$table, 'CHECK']);
        } catch (Exception) {
            return [];
        }
    }

    public function getSequences(): array
    {
        return [];
    }

    public function getTables(): array
    {
        try {
            return DB::connection($this->connection)->select("
                SELECT TABLE_NAME as name
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME
            ");
        } catch (Exception) {
            return [];
        }
    }
}
