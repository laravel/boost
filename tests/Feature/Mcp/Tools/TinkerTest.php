<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Request;

test('executes simple php code', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'return 2 + 2;']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 4,
            'type' => 'integer',
        ]);
});

test('executes code with output', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'echo "Hello World"; return "test";']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 'test',
            'output' => 'Hello World',
            'type' => 'string',
        ]);
});

test('accesses laravel facades', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'return config("app.name");']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => config('app.name'),
            'type' => 'string',
        ]);
});

test('creates objects', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'return new stdClass();']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'type' => 'object',
            'class' => 'stdClass',
        ]);
});

test('handles syntax errors', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'invalid syntax here']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'type' => \Psy\Exception\ParseErrorException::class,
        ])
        ->toolJsonContent(function ($data): void {
            expect($data)->toHaveKey('error');
        });
});

test('handles runtime errors', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'throw new Exception("Test error");']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray([
            'type' => 'Exception',
            'error' => 'Test error',
        ])
        ->toolJsonContent(function ($data): void {
            expect($data)->toHaveKey('error');
        });
});

test('captures multiple outputs', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'echo "First"; echo "Second"; return "done";']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 'done',
            'output' => 'FirstSecond',
        ]);
});

test('executes code with different return types', function (string $code, mixed $expectedResult, string $expectedType): void {
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

test('handles empty code', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => '']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'type' => 'object',
            'class' => \Psy\CodeCleaner\NoReturnValue::class,
        ]);
});

test('handles code with no return statement', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => '$x = 5;']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 5,
            'type' => 'integer',
        ]);
});

test('should register only in local environment', function (): void {
    $tool = new Tinker;

    app()->detectEnvironment(fn (): string => 'local');

    expect($tool->eligibleForRegistration())->toBeTrue();
});

test('executes expression-only code without return or semicolon', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => '2 + 3']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'result' => 5,
            'type' => 'integer',
        ]);
});

test('executes code without trailing semicolon', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'echo "Hi"']));

    expect($response)->isToolResult()
        ->toolJsonContentToMatchArray([
            'output' => 'Hi',
        ]);
});
