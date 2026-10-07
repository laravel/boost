<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Boost\Mcp\Tools\DatabaseSchema\NullSchemaDriver;

beforeEach(function (): void {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => database_path('null_schema_driver_testing.sqlite'),
        'prefix' => '',
    ]);

    if (! is_file($file = database_path('null_schema_driver_testing.sqlite'))) {
        touch($file);
    }

    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });

    $this->driver = new NullSchemaDriver('testing');
});

afterEach(function (): void {
    DB::disconnect('testing');

    $dbFile = database_path('null_schema_driver_testing.sqlite');

    if (File::exists($dbFile)) {
        File::delete($dbFile);
    }
});

test('returns tables from the schema builder', function (): void {
    $tables = $this->driver->getTables();

    expect(array_column($tables, 'name'))->toBe(['users']);
});
