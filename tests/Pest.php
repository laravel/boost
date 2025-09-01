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

use Laravel\Mcp\Server\Tools\ToolResult;

uses(Tests\TestCase::class)->in('Feature');

expect()->extend('isToolResult', function () {
    return $this->toBeInstanceOf(ToolResult::class);
});

expect()->extend('toolTextContains', function (mixed ...$needles) {
    /** @var ToolResult $this->value */
    $output = implode('', array_column($this->value->toArray()['content'], 'text'));
    expect($output)->toContain(...func_get_args());

    return $this;
});

expect()->extend('toolTextDoesNotContain', function (mixed ...$needles) {
    /** @var ToolResult $this->value */
    $output = implode('', array_column($this->value->toArray()['content'], 'text'));
    expect($output)->not->toContain(...func_get_args());

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

expect()->extend('toolJsonContent', function (callable $callback) {
    /** @var ToolResult $this->value */
    $data = $this->value->toArray();
    $content = json_decode($data['content'][0]['text'], true);
    $callback($content);

    return $this;
});

expect()->extend('toolJsonContentToMatchArray', function (array $expectedArray) {
    /** @var ToolResult $this->value */
    $data = $this->value->toArray();
    $content = json_decode($data['content'][0]['text'], true);
    expect($content)->toMatchArray($expectedArray);

    return $this;
});

function fixture(string $name): string
{
    return file_get_contents(\Pest\testDirectory('fixtures/'.$name));
}
