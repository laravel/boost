<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\Tinker;
use Laravel\Mcp\Request;
use Laravel\Tinker\TinkerServiceProvider;

beforeEach(function (): void {
    $this->app->register(TinkerServiceProvider::class);
});

test('executes code and returns output', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'echo "Hello World";']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Hello World');
});

test('handles errors gracefully', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => 'invalid syntax here']));

    expect((string) $response->content())->toContain('Syntax error');
});

test('strips php tags from code', function (): void {
    $tool = new Tinker;
    $response = $tool->handle(new Request(['code' => '<?php echo "stripped"; ?>']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('stripped');
});

test('should register only in local environment', function (): void {
    $tool = new Tinker;

    app()->detectEnvironment(fn (): string => 'local');

    expect($tool->eligibleForRegistration())->toBeTrue();
});
