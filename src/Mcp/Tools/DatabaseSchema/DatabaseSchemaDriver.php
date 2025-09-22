<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools\DatabaseSchema;

abstract class DatabaseSchemaDriver
{
    public function __construct(protected $connection = null) {}

    abstract public function getViews(): array;

    abstract public function getStoredProcedures(): array;

    abstract public function getFunctions(): array;

    abstract public function getTriggers(?string $table = null): array;

    abstract public function getCheckConstraints(string $table): array;

    abstract public function getSequences(): array;
}
