<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools\DatabaseSchema;

use Closure;
use Illuminate\Support\Facades\DB;

class SchemaDriverFactory
{
    /**
     * @var array<string, Closure(?string): DatabaseSchemaDriver>
     */
    protected static array $customDrivers = [];

    /**
     * @param  Closure(?string): DatabaseSchemaDriver|class-string<DatabaseSchemaDriver>  $resolver
     */
    public static function register(string $driverName, Closure|string $resolver): void
    {
        static::$customDrivers[$driverName] = static::resolveCustomDriverResolver($resolver);
    }

    public static function flush(): void
    {
        static::$customDrivers = [];
    }

    public static function make(?string $connection = null): DatabaseSchemaDriver
    {
        $connectionName = $connection ?? config('database.default');
        $driverName = config("database.connections.{$connectionName}.driver");

        if (! is_string($driverName) || $driverName === '') {
            $driverName = DB::connection($connectionName)->getDriverName();
        }

        if (isset(static::$customDrivers[$driverName])) {
            return (static::$customDrivers[$driverName])($connection);
        }

        return match ($driverName) {
            'mysql', 'mariadb' => new MySQLSchemaDriver($connection),
            'pgsql' => new PostgreSQLSchemaDriver($connection),
            'sqlite' => new SQLiteSchemaDriver($connection),
            default => new NullSchemaDriver($connection),
        };
    }

    /**
     * @param  Closure(?string): DatabaseSchemaDriver|class-string<DatabaseSchemaDriver>  $resolver
     * @return Closure(?string): DatabaseSchemaDriver
     */
    protected static function resolveCustomDriverResolver(Closure|string $resolver): Closure
    {
        if ($resolver instanceof Closure) {
            return $resolver;
        }

        return static fn (?string $connection): DatabaseSchemaDriver => new $resolver($connection);
    }
}
