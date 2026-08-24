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
                ->and($exampleTable['columns']['id'])->not->toHaveKey('nullable')
                ->and($exampleTable['columns']['id'])->not->toHaveKey('auto_increment')
                ->and($exampleTable['columns']['id'])->not->toHaveKey('default');
        });
});

test('it includes column details when include_column_details is true', function (): void {
    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request(['include_column_details' => true]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            $exampleTable = $schemaArray['tables']['examples'];
            expect($exampleTable['columns'])->toHaveKeys(['id', 'name'])
                ->and($exampleTable['columns']['id']['type'])->toContain('integer')
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

test('it returns only table names and column types in summary mode', function (): void {
    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request(['summary' => true]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray)->toHaveKey('engine')
                ->and($schemaArray)->toHaveKey('tables')
                ->and($schemaArray)->not->toHaveKey('views')
                ->and($schemaArray)->not->toHaveKey('routines');

            $exampleTable = $schemaArray['tables']['examples'];
            expect($exampleTable)->toBeArray()
                ->and($exampleTable)->toHaveKeys(['id', 'name'])
                ->and($exampleTable['id'])->toContain('integer')
                ->and($exampleTable['name'])->toContain('varchar')
                ->and($exampleTable)->not->toHaveKey('columns')
                ->and($exampleTable)->not->toHaveKey('indexes')
                ->and($exampleTable)->not->toHaveKey('foreign_keys');
        });
});

test('it filters tables in summary mode', function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email');
    });

    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request(['summary' => true, 'filter' => 'user']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables'])->toHaveKey('users')
                ->and($schemaArray['tables'])->not->toHaveKey('examples');

            expect($schemaArray['tables']['users'])->toHaveKeys(['id', 'email'])
                ->and($schemaArray['tables']['users']['id'])->toContain('integer')
                ->and($schemaArray['tables']['users']['email'])->toContain('varchar');
        });
});

test('it returns table details on a connection with a table prefix', function (): void {
    config()->set('database.connections.testing.prefix', 'boost_');
    DB::purge('testing');

    Schema::create('teams', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email')->index();
        $table->foreignId('team_id')->constrained('teams');
    });

    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request(['filter' => 'user']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables'])->toHaveKey('users');

            $userTable = $schemaArray['tables']['users'];
            expect($userTable['columns'])->toHaveKeys(['id', 'email', 'team_id'])
                ->and($userTable['columns']['email']['type'])->toContain('varchar')
                ->and($userTable['indexes'])->not->toBeEmpty()
                ->and($userTable['foreign_keys'])->not->toBeEmpty();
        });
});

test('it returns column types on a connection with a table prefix in summary mode', function (): void {
    config()->set('database.connections.testing.prefix', 'boost_');
    DB::purge('testing');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email');
    });

    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request(['summary' => true, 'filter' => 'user']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables'])->toHaveKey('users')
                ->and($schemaArray['tables']['users'])->toHaveKeys(['id', 'email'])
                ->and($schemaArray['tables']['users']['email'])->toContain('varchar');
        });
});

test('it restores the connection table prefix after reading the schema', function (): void {
    config()->set('database.connections.testing.prefix', 'boost_');
    DB::purge('testing');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
    });

    (new DatabaseSchema)->handle(new Request(['filter' => 'user']));

    expect(DB::connection('testing')->getTablePrefix())->toBe('boost_');
});

test('it restores the connection table prefix when reading the schema fails', function (): void {
    config()->set('database.connections.testing.prefix', 'boost_');
    DB::purge('testing');

    $tool = new class extends DatabaseSchema
    {
        protected function getAllTables(?string $connection): array
        {
            throw new RuntimeException('catalog unavailable');
        }
    };

    expect(fn (): mixed => $tool->handle(new Request))->toThrow(RuntimeException::class)
        ->and(DB::connection('testing')->getTablePrefix())->toBe('boost_');
});

test('it returns table details for tables that do not carry the connection prefix', function (): void {
    config()->set('database.connections.testing.prefix', 'boost_');
    DB::purge('testing');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email');
    });

    DB::statement('create table legacy_audit (legacy_column varchar(255))');

    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request);

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables']['legacy_audit']['columns'])->toHaveKey('legacy_column')
                ->and($schemaArray['tables']['users']['columns'])->toHaveKeys(['id', 'email']);
        });
});

test('it strips the prefix from foreign key targets and view names', function (): void {
    config()->set('database.connections.testing.prefix', 'boost_');
    DB::purge('testing');

    Schema::create('teams', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('team_id')->constrained('teams');
    });

    DB::statement('create view boost_active_users as select id from boost_users');

    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request(['include_views' => true, 'filter' => 'user']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables']['users']['foreign_keys'][0]['foreign_table'])->toBe('teams')
                ->and(collect($schemaArray['views'])->pluck('name'))->toContain('active_users');
        });
});

test('it strips the prefix from view rows that name the view something other than "name"', function (): void {
    config()->set('database.connections.testing.prefix', 'boost_');
    DB::purge('testing');

    $tool = new class extends DatabaseSchema
    {
        /** @return array<string, mixed> */
        public function strip(array $result): array
        {
            return $this->stripTablePrefix('testing', $result);
        }
    };

    $result = $tool->strip([
        'tables' => [],
        'views' => [
            (object) ['schemaname' => 'public', 'viewname' => 'boost_active_users'],
            ['name' => 'boost_archived_users'],
        ],
    ]);

    expect($result['views'][0]->viewname)->toBe('active_users')
        ->and($result['views'][1]['name'])->toBe('archived_users');
});

test('it leaves names untouched on a connection without a table prefix', function (): void {
    $tool = new DatabaseSchema;
    $response = $tool->handle(new Request);

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function (array $schemaArray): void {
            expect($schemaArray['tables'])->toHaveKey('examples')
                ->and($schemaArray['tables']['examples']['columns'])->toHaveKeys(['id', 'name']);
        });
});
