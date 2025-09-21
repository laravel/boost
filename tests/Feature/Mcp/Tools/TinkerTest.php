<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Request;

test('executes simple php code', function () {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'return 2 + 2;']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 4,
            'type' => 'integer',
        ]);
});

test('executes code with output', function () {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'echo "Hello World"; return "test";']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 'test',
            'output' => 'Hello World',
            'type' => 'string',
        ]);
});

test('accesses laravel facades', function () {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'return config("app.name");']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => config('app.name'),
            'type' => 'string',
        ]);
});

test('creates objects', function () {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'return new stdClass();']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'type' => 'object',
            'class' => 'stdClass',
        ]);
});

test('handles syntax errors', function () {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'invalid syntax here']));

    expect($response)->isToolResult()
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
    $response = $tool->handle(new Request(['code' => 'throw new Exception("Test error");']));

    expect($response)->isToolResult()
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
    $response = $tool->handle(new Request(['code' => 'echo "First"; echo "Second"; return "done";']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 'done',
            'output' => 'FirstSecond',
        ]);
});

test('executes code with different return types', function (string $code, mixed $expectedResult, string $expectedType) {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => $code]));

    expect($response)->isToolResult()
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
    $response = $tool->handle(new Request(['code' => '']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => false,
            'type' => 'boolean',
        ]);
});

test('handles code with no return statement', function () {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => '$x = 5;']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => null,
            'type' => 'NULL',
        ]);
});

test('should register only in local environment', function () {
    $tool = new Tinker;

    app()->detectEnvironment(function () {
        return 'local';
    });

    expect($tool->eligibleForRegistration(Mockery::mock(Request::class)))->toBeTrue();
});
