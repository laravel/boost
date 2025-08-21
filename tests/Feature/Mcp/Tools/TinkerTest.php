<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Server\Tools\ToolResult;

function getToolResultData(ToolResult $result): array
{
    $data = $result->toArray();

    return json_decode($data['content'][0]['text'], true);
}

test('executes simple php code', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'return 2 + 2;']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['result'])->toBe(4)
        ->and($data['type'])->toBe('integer');
});

test('executes code with output', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'echo "Hello World"; return "test";']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['result'])->toBe('test')
        ->and($data['output'])->toBe('Hello World')
        ->and($data['type'])->toBe('string');
});

test('accesses laravel facades', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'return config("app.name");']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['result'])->toBeString()
        ->and($data['result'])->toBe(config('app.name'))
        ->and($data['type'])->toBe('string');
});

test('creates objects', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'return new stdClass();']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['type'])->toBe('object')
        ->and($data['class'])->toBe('stdClass');
});

test('handles syntax errors', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'invalid syntax here']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $resultArray = $result->toArray();
    expect($resultArray['isError'])->toBeFalse();

    $data = getToolResultData($result);
    expect($data)->toHaveKey('error')
        ->and($data)->toHaveKey('type')
        ->and($data['type'])->toBe('ParseError');
});

test('handles runtime errors', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'throw new Exception("Test error");']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $resultArray = $result->toArray();
    expect($resultArray['isError'])->toBeFalse();

    $data = getToolResultData($result);
    expect($data)->toHaveKey('error')
        ->and($data['type'])->toBe('Exception')
        ->and($data['error'])->toBe('Test error');
});

test('captures multiple outputs', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'echo "First"; echo "Second"; return "done";']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['result'])->toBe('done')
        ->and($data['output'])->toBe('FirstSecond');
});

test('executes code with different return types', function (string $code, mixed $expectedResult, string $expectedType) {
    $tool = new Tinker;
    $result = $tool->handle(['code' => $code]);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['result'])->toBe($expectedResult)
        ->and($data['type'])->toBe($expectedType);
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

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['result'])->toBeFalse()
        ->and($data['type'])->toBe('boolean');
});

test('handles code with no return statement', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => '$x = 5;']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['result'])->toBeNull()
        ->and($data['type'])->toBe('NULL');
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

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['result'])->toBe(4)
        ->and($data['type'])->toBe('integer');
});

test('uses default timeout when not specified', function () {
    $tool = new Tinker;
    $result = $tool->handle(['code' => 'return 2 + 2;']);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data['result'])->toBe(4)
        ->and($data['type'])->toBe('integer');
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

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = getToolResultData($result);
    expect($data)->toHaveKey('error')
        ->and($data['error'])->toMatch('/(Maximum execution time|Code execution timed out)/');
});
