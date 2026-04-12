<?php

declare(strict_types=1);

use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Support\Config;

beforeEach(function (): void {
    (new Config)->flush();
});

afterEach(function (): void {
    (new Config)->flush();
});

function buildInstallCommandWithEnforceTestsState(Config $config, array $features = ['guidelines']): InstallCommand
{
    $command = Mockery::mock(InstallCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    // determineTestEnforcement() should never be called when a value is already persisted.
    // Stub it to fail the test if it is invoked - the whole point of the fix is to skip it.
    $command->shouldNotReceive('determineTestEnforcement');

    $reflect = new ReflectionClass(InstallCommand::class);

    $reflect->getProperty('config')->setValue($command, $config);
    $reflect->getProperty('selectedBoostFeatures')->setValue($command, collect($features));

    return $command;
}

test('resolveTestEnforcement returns the persisted true value without running detection', function (): void {
    $config = new Config;
    $config->setEnforceTests(true);

    $command = buildInstallCommandWithEnforceTestsState($config);

    $method = (new ReflectionClass(InstallCommand::class))->getMethod('resolveTestEnforcement');

    expect($method->invoke($command))->toBeTrue();
});

test('resolveTestEnforcement returns the persisted false value without running detection', function (): void {
    $config = new Config;
    $config->setEnforceTests(false);

    $command = buildInstallCommandWithEnforceTestsState($config);

    $method = (new ReflectionClass(InstallCommand::class))->getMethod('resolveTestEnforcement');

    expect($method->invoke($command))->toBeFalse();
});

test('resolveTestEnforcement falls back to detection when no value is persisted', function (): void {
    $config = new Config;
    // Intentionally do not call setEnforceTests() - config has no stored value.

    $command = Mockery::mock(InstallCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $command->shouldReceive('determineTestEnforcement')->once()->andReturn(true);

    $reflect = new ReflectionClass(InstallCommand::class);
    $reflect->getProperty('config')->setValue($command, $config);
    $reflect->getProperty('selectedBoostFeatures')->setValue($command, collect(['guidelines']));

    $method = $reflect->getMethod('resolveTestEnforcement');

    expect($method->invoke($command))->toBeTrue();
});

test('storeConfig persists enforce tests to boost.json when guidelines are installed', function (): void {
    $config = new Config;

    $command = Mockery::mock(InstallCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $command->shouldReceive('isExplicitFlagMode')->andReturn(true);

    $reflect = new ReflectionClass(InstallCommand::class);
    $reflect->getProperty('config')->setValue($command, $config);
    $reflect->getProperty('selectedBoostFeatures')->setValue($command, collect(['guidelines']));
    $reflect->getProperty('selectedThirdPartyPackages')->setValue($command, collect());
    $reflect->getProperty('selectedAgents')->setValue($command, collect());
    $reflect->getProperty('enforceTests')->setValue($command, true);

    $reflect->getMethod('storeConfig')->invoke($command);

    expect((new Config)->getEnforceTests())->toBeTrue();
});

test('storeConfig persists a false enforce tests value to boost.json', function (): void {
    $config = new Config;

    $command = Mockery::mock(InstallCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $command->shouldReceive('isExplicitFlagMode')->andReturn(true);

    $reflect = new ReflectionClass(InstallCommand::class);
    $reflect->getProperty('config')->setValue($command, $config);
    $reflect->getProperty('selectedBoostFeatures')->setValue($command, collect(['guidelines']));
    $reflect->getProperty('selectedThirdPartyPackages')->setValue($command, collect());
    $reflect->getProperty('selectedAgents')->setValue($command, collect());
    $reflect->getProperty('enforceTests')->setValue($command, false);

    $reflect->getMethod('storeConfig')->invoke($command);

    expect((new Config)->getEnforceTests())->toBeFalse();
});

test('storeConfig does not write enforce tests when guidelines are not installed', function (): void {
    $config = new Config;

    $command = Mockery::mock(InstallCommand::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $command->shouldReceive('isExplicitFlagMode')->andReturn(true);

    $reflect = new ReflectionClass(InstallCommand::class);
    $reflect->getProperty('config')->setValue($command, $config);
    $reflect->getProperty('selectedBoostFeatures')->setValue($command, collect(['mcp']));
    $reflect->getProperty('selectedThirdPartyPackages')->setValue($command, collect());
    $reflect->getProperty('selectedAgents')->setValue($command, collect());
    $reflect->getProperty('enforceTests')->setValue($command, true);

    $reflect->getMethod('storeConfig')->invoke($command);

    expect((new Config)->getEnforceTests())->toBeNull();
});
