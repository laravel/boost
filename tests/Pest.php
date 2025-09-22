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

use Laravel\Mcp\Response;

uses(Tests\TestCase::class)->in('Feature');

expect()->extend('isToolResult', fn () => $this->toBeInstanceOf(Response::class));

expect()->extend('toolTextContains', function (mixed ...$needles) {
    /** @var Response $this->value */
    $output = (string) $this->value->content();
    expect($output)->toContain(...func_get_args());

    return $this;
});

expect()->extend('toolTextDoesNotContain', function (mixed ...$needles) {
    /** @var Response $this->value */
    $output = (string) $this->value->content();
    expect($output)->not->toContain(...func_get_args());

    return $this;
});

expect()->extend('toolHasError', function () {
    expect($this->value->isError())->toBeTrue();

    return $this;
});

expect()->extend('toolHasNoError', function () {
    expect($this->value->isError())->toBeFalse();

    return $this;
});

expect()->extend('toolJsonContent', function (callable $callback) {
    /** @var Response $this->value */
    $content = json_decode((string) $this->value->content(), true);
    $callback($content);

    return $this;
});

expect()->extend('toolJsonContentToMatchArray', function (array $expectedArray) {
    /** @var Response $this->value */
    $content = json_decode((string) $this->value->content(), true);
    expect($content)->toMatchArray($expectedArray);

    return $this;
});

function fixture(string $name): string
{
    return file_get_contents(\Pest\testDirectory('fixtures/'.$name));
}
