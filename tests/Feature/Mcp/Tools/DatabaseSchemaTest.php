<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    // Switch the default connection to a file-backed SQLite DB.
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => database_path('testing.sqlite'),
        'prefix' => '',
    ]);

    // Ensure the DB file exists
    if (! is_file($file = database_path('testing.sqlite'))) {
        touch($file);
    }

    // Build a throw-away table that we expect in the dump.
    Schema::dropIfExists('examples');
    Schema::create('examples', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
});

afterEach(function (): void {
    $dbFile = database_path('testing.sqlite');
    if (File::exists($dbFile)) {
        File::delete($dbFile);
    }
});

test('it returns structured database schema', function (): void {
    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'engine' => 'sqlite',
        ])
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray)->toHaveKey('tables')
                ->and($schemaArray['tables'])->toHaveKey('examples');

            $exampleTable = $schemaArray['tables']['examples'];
            expect($exampleTable)->toHaveKeys(['columns', 'indexes', 'foreign_keys', 'triggers', 'check_constraints'])
                ->and($exampleTable['columns'])->toHaveKeys(['id', 'name'])
                ->and($exampleTable['columns']['id']['type'])->toBe('integer')
                ->and($exampleTable['columns']['name']['type'])->toBe('varchar')
                ->and($schemaArray)->toHaveKey('global')
                ->and($schemaArray['global'])->toHaveKeys(['views', 'stored_procedures', 'functions', 'sequences']);

        });
});

test('it filters tables by name', function (): void {
    // Create another table
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email');
    });

    $tool = new DatabaseSchema;

    // Test filtering for 'example'
    $response = $tool->handle(new Request(['filter' => 'example']));
    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables'])->toHaveKey('examples')
                ->and($schemaArray['tables'])->not->toHaveKey('users');
        });

    // Test filtering for 'user'
    $response = $tool->handle(new Request(['filter' => 'user']));
    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables'])->toHaveKey('users')
                ->and($schemaArray['tables'])->not->toHaveKey('examples');
        });
});
