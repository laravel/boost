<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => database_path('testing.sqlite'),
        'prefix' => '',
    ]);

    if (! is_file($file = database_path('testing.sqlite'))) {
        touch($file);
    }

    Schema::dropIfExists('examples');
    Schema::create('examples', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
});

afterEach(function (): void {
    DB::disconnect('testing');

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
                ->and($schemaArray['tables'])->toHaveKey('examples')
                ->and($schemaArray)->not->toHaveKey('views')
                ->and($schemaArray)->not->toHaveKey('routines');

            $exampleTable = $schemaArray['tables']['examples'];
            expect($exampleTable)->toHaveKeys(['columns', 'indexes', 'foreign_keys', 'triggers', 'check_constraints'])
                ->and($exampleTable['columns'])->toHaveKeys(['id', 'name'])
                ->and($exampleTable['columns']['id']['type'])->toContain('integer')
                ->and($exampleTable['columns']['name']['type'])->toContain('varchar')
                ->and($exampleTable['columns']['id']['nullable'])->toBeBool()
                ->and($exampleTable['columns']['id']['auto_increment'])->toBeTrue()
                ->and($exampleTable['columns']['id'])->toHaveKey('default')
                ->and($exampleTable['columns']['name']['nullable'])->toBeFalse()
                ->and($exampleTable['columns']['name']['auto_increment'])->toBeFalse();
        });
});

test('it falls back to direct query when cache is unreachable', function (): void {
    Cache::shouldReceive('remember')
        ->andThrow(new RuntimeException('Cache driver unreachable'));

    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray)->toHaveKey('engine')
                ->and($schemaArray)->toHaveKey('tables')
                ->and($schemaArray['tables'])->toHaveKey('examples');
        });
});

test('it filters tables by name', function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email');
    });

    $tool = new DatabaseSchema;

    $response = $tool->handle(new Request(['filter' => 'example']));
    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables'])->toHaveKey('examples')
                ->and($schemaArray['tables'])->not->toHaveKey('users');
        });

    $response = $tool->handle(new Request(['filter' => 'user']));
    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables'])->toHaveKey('users')
                ->and($schemaArray['tables'])->not->toHaveKey('examples');
        });
});

test('it includes views when include_views is true', function (): void {
    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request(['include_views' => true]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray)->toHaveKey('views')
                ->and($schemaArray)->toHaveKey('tables')
                ->and($schemaArray)->not->toHaveKey('routines');
        });
});

test('it includes routines when include_routines is true', function (): void {
    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request(['include_routines' => true]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray)->toHaveKey('routines')
                ->and($schemaArray['routines'])->toHaveKeys(['stored_procedures', 'functions', 'sequences'])
                ->and($schemaArray)->toHaveKey('tables')
                ->and($schemaArray)->not->toHaveKey('views');
        });
});

test('it includes both views and routines when both are true', function (): void {
    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request(['include_views' => true, 'include_routines' => true]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray)->toHaveKey('views')
                ->and($schemaArray)->toHaveKey('routines')
                ->and($schemaArray)->toHaveKey('tables')
                ->and($schemaArray)->toHaveKey('engine');
        });
});
