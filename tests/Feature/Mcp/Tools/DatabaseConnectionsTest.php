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
        ->toolJsonContentToMatchArray([
            'default_connection' => 'mysql',
            'connections' => ['mysql', 'pgsql', 'sqlite'],
        ]);
});

test('it returns empty connections when none configured', function () {
    config()->set('database.connections', []);

    $tool = new DatabaseConnections;
    $result = $tool->handle([]);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'default_connection' => 'mysql',
            'connections' => [],
        ]);
});
