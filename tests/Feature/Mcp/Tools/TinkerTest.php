<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\Tinker;

test('should register only in local environment', function (): void {
    $tool = new Tinker;

    app()->detectEnvironment(fn (): string => 'local');

    expect($tool->eligibleForRegistration())->toBeTrue();
});
