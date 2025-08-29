<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Tests\TestCase::class)->in('Feature');

expect()->extend('isToolResult', function () {
    return $this->toBeInstanceOf(\Laravel\Mcp\Server\Tools\ToolResult::class);
});

expect()->extend('toolTextContains', function (mixed ...$needles) {
    /** @var \Laravel\Mcp\Server\Tools\ToolResult $this->value */
    $output = implode('', array_column($this->value->toArray()['content'], 'text'));
    expect($output)->toContain(...func_get_args());

    return $this;
});

expect()->extend('toolHasError', function () {
    expect($this->value->toArray()['isError'])->toBeTrue();

    return $this;
});

expect()->extend('toolHasNoError', function () {
    expect($this->value->toArray()['isError'])->toBeFalse();

    return $this;
});

function fixture(string $name): string
{
    return file_get_contents(\Pest\testDirectory('fixtures/'.$name));
}
