<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\Tinker;

test('executes simple php code', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'return 2 + 2;']);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 4,
            'type' => 'integer',
        ]);
});

test('executes code with output', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'echo "Hello World"; return "test";']);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 'test',
            'output' => 'Hello World',
            'type' => 'string',
        ]);
});

test('accesses laravel facades', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'return config("app.name");']);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => config('app.name'),
            'type' => 'string',
        ]);
});

test('creates objects', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'return new stdClass();']);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'type' => 'object',
            'class' => 'stdClass',
        ]);
});

test('handles syntax errors', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'invalid syntax here']);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'type' => 'ParseError',
        ])
        ->toolJsonContent(function ($data) {
            expect($data)->toHaveKey('error');
        });
});

test('handles runtime errors', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'throw new Exception("Test error");']);

    expect($result)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'type' => 'Exception',
            'error' => 'Test error',
        ])
        ->toolJsonContent(function ($data) {
            expect($data)->toHaveKey('error');
        });
});

test('captures multiple outputs', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'echo "First"; echo "Second"; return "done";']);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 'done',
            'output' => 'FirstSecond',
        ]);
});

test('executes code with different return types', function (string $code, mixed $expectedResult, string $expectedType) {
    $tool = new Tinker;
    $result = $tool->handle(['code' => $code]);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => $expectedResult,
            'type' => $expectedType,
        ]);
})->with([
    'integer' => ['return 42;', 42, 'integer'],
    'string' => ['return "hello";', 'hello', 'string'],
    'boolean true' => ['return true;', true, 'boolean'],
    'boolean false' => ['return false;', false, 'boolean'],
    'null' => ['return null;', null, 'NULL'],
    'array' => ['return [1, 2, 3];', [1, 2, 3], 'array'],
    'float' => ['return 3.14;', 3.14, 'double'],
]);

test('handles empty code', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => '']);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => false,
            'type' => 'boolean',
        ]);
});

test('handles code with no return statement', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => '$x = 5;']);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => null,
            'type' => 'NULL',
        ]);
});

test('should register only in local environment', function () {
    $tool = new Tinker;

    // Test in local environment
    app()->detectEnvironment(function () {
        return 'local';
    });

    expect($tool->shouldRegister())->toBeTrue();
});

test('uses custom timeout parameter', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'return 2 + 2;', 'timeout' => 10]);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 4,
            'type' => 'integer',
        ]);
});

test('uses default timeout when not specified', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'return 2 + 2;']);

    expect($result)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 4,
            'type' => 'integer',
        ]);
});

test('times out when code takes too long', function () {
    $tool = new Tinker;

    // Code that will take more than 1 second to execute
    $slowCode = '
        $start = microtime(true);
        while (microtime(true) - $start < 1.2) {
            usleep(50000); // Don\'t waste entire CPU
        }
        return "should not reach here";
    ';

    $result = $tool->handle(['code' => $slowCode, 'timeout' => 1]);

    expect($result)->isToolResult()
        ->toolJsonContent(function ($data) {
            expect($data)->toHaveKey('error')
                ->and($data['error'])->toMatch('/(Maximum execution time|Code execution timed out)/');
        });
});
