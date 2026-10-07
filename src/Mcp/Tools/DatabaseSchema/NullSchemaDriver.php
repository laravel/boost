<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools\DatabaseSchema;

use Exception;
use Illuminate\Support\Facades\Schema;

class NullSchemaDriver extends DatabaseSchemaDriver
{
    public function getViews(): array
    {
        return [];
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
        return [];
    }

    public function getCheckConstraints(string $table): array
    {
        return [];
    }

    public function getSequences(): array
    {
        return [];
    }

    public function getTables(): array
    {
        try {
            return Schema::connection($this->connection)->getTables();
        } catch (Exception) {
            return [];
        }
    }
}
