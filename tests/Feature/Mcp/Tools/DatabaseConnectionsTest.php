<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\DatabaseConnections;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    config()->set('database.default', 'mysql');
    config()->set('database.connections', [
        'mysql' => ['driver' => 'mysql'],
        'pgsql' => ['driver' => 'pgsql'],
        'sqlite' => ['driver' => 'sqlite'],
    ]);
});

test('it returns database connections', function (): void {
    $tool = new DatabaseConnections;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'default_connection' => 'mysql',
            'connections' => ['mysql', 'pgsql', 'sqlite'],
        ]);
});

test('it returns empty connections when none configured', function (): void {
    config()->set('database.connections', []);

    $tool = new DatabaseConnections;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'default_connection' => 'mysql',
            'connections' => [],
        ]);
});
