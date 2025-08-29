<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\DatabaseConnections;

beforeEach(function () {
    config()->set('database.default', 'mysql');
    config()->set('database.connections', [
        'mysql' => ['driver' => 'mysql'],
        'pgsql' => ['driver' => 'pgsql'],
        'sqlite' => ['driver' => 'sqlite'],
    ]);
});

test('it returns database connections', function () {
    $tool = new DatabaseConnections;
    $result = $tool->handle([]);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($content) {
            expect($content['default_connection'])->toBe('mysql')
                ->and($content['connections'])->toHaveCount(3)
                ->and($content['connections'])->toContain('mysql')
                ->and($content['connections'])->toContain('pgsql')
                ->and($content['connections'])->toContain('sqlite');
        });
});

test('it returns empty connections when none configured', function () {
    config()->set('database.connections', []);

    $tool = new DatabaseConnections;
    $result = $tool->handle([]);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($content) {
            expect($content['default_connection'])->toBe('mysql')
                ->and($content['connections'])->toHaveCount(0);
        });
});
