<?php

declare(strict_types=1);

use Laravel\Boost\Console\InstallCommand;

function callDetermineTestEnforcement(): bool
{
    $command = Mockery::mock(InstallCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $reflect = new ReflectionClass(InstallCommand::class);

    return $reflect->getMethod('determineTestEnforcement')->invoke($command);
}

test('returns config value when boost.enforce_tests is explicitly set to true', function (): void {
    config(['boost.enforce_tests' => true]);

    expect(callDetermineTestEnforcement())->toBeTrue();
});

test('returns config value when boost.enforce_tests is explicitly set to false', function (): void {
    config(['boost.enforce_tests' => false]);

    expect(callDetermineTestEnforcement())->toBeFalse();
});

test('returns false when phpunit binary is not installed', function (): void {
    config(['boost.enforce_tests' => null]);

    // The vendor/bin/phpunit check will use the actual base_path which
    // in the test environment points to the workbench - where phpunit
    // binary does not exist at vendor/bin/phpunit
    expect(callDetermineTestEnforcement())->toBeFalse();
});
