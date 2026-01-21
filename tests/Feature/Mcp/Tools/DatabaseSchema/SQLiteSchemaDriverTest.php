<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Boost\Mcp\Tools\DatabaseSchema\SQLiteSchemaDriver;

beforeEach(function (): void {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => database_path('schema_driver_testing.sqlite'),
        'prefix' => '',
    ]);

    if (! is_file($file = database_path('schema_driver_testing.sqlite'))) {
        touch($file);
    }

    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
    });

    $this->driver = new SQLiteSchemaDriver('testing');
});

afterEach(function (): void {
    DB::disconnect('testing');

    $dbFile = database_path('schema_driver_testing.sqlite');

    if (File::exists($dbFile)) {
        File::delete($dbFile);
    }
});

test('returns views from database', function (): void {
    DB::connection('testing')->statement('CREATE VIEW active_users AS SELECT * FROM users WHERE id > 0');

    $views = $this->driver->getViews();

    expect($views)->toHaveCount(1)
        ->and($views[0]->name)->toBe('active_users')
        ->and($views[0]->sql)->toContain('SELECT * FROM users');
});

test('returns empty array when no views exist', function (): void {
    $views = $this->driver->getViews();

    expect($views)->toBe([]);
});

test('returns all triggers when no table specified', function (): void {
    DB::connection('testing')->statement('
        CREATE TRIGGER users_audit AFTER INSERT ON users BEGIN
            SELECT 1;
        END
    ');

    $triggers = $this->driver->getTriggers();

    expect($triggers)->toHaveCount(1)
        ->and($triggers[0]->name)->toBe('users_audit');
});

test('returns only triggers for specified table', function (): void {
    Schema::create('posts', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
    });

    DB::connection('testing')->statement('
        CREATE TRIGGER users_trigger AFTER INSERT ON users BEGIN
            SELECT 1;
        END
    ');

    DB::connection('testing')->statement('
        CREATE TRIGGER posts_trigger AFTER INSERT ON posts BEGIN
            SELECT 1;
        END
    ');

    $triggers = $this->driver->getTriggers('users');

    expect($triggers)->toHaveCount(1)
        ->and($triggers[0]->name)->toBe('users_trigger');
});

test('returns empty array when no triggers exist', function (): void {
    $triggers = $this->driver->getTriggers();

    expect($triggers)->toBe([]);
});
