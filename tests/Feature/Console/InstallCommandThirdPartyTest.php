<?php

declare(strict_types=1);

use Illuminate\Console\ManuallyFailedException;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;
use Laravel\Boost\Console\Enums\Theme;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Cloud;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    (new Config)->flush();
});

afterEach(function (): void {
    (new Config)->flush();
    Mockery::close();
});

function makeThirdPartyInstallCommand(BufferedOutput $output): InstallCommand
{
    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('isInstalled')->andReturn(false);

    $sail = Mockery::mock(Sail::class);
    $sail->shouldReceive('isInstalled')->andReturn(false);
    $sail->shouldReceive('isActive')->andReturn(false);

    $terminal = Mockery::mock(Terminal::class);
    $terminal->shouldReceive('initDimensions');

    $command = new class(app(AgentsDetector::class), Mockery::mock(Cloud::class), new Config, $nightwatch, $sail, $terminal) extends InstallCommand
    {
        protected function displayBoostHeader(string $featureName, string $projectName, ?Theme $theme = null): void {}
    };

    $command->setLaravel(app());
    $command->setOutput(new OutputStyle(new ArrayInput([]), $output));

    return $command;
}

function invokeThirdPartyMethod(InstallCommand $command, string $method, mixed ...$args): mixed
{
    return (new ReflectionMethod($command, $method))->invoke($command, ...$args);
}

function discoveredPackages(): Collection
{
    return collect([
        'vendor/one' => new ThirdPartyPackage('vendor/one', true, false),
        'vendor/two' => new ThirdPartyPackage('vendor/two', false, true),
    ]);
}

it('selects exactly the packages named by --packages', function (): void {
    $command = makeThirdPartyInstallCommand(new BufferedOutput);
    $command->setInput(new ArrayInput(['--packages' => 'vendor/two'], $command->getDefinition()));

    expect(invokeThirdPartyMethod($command, 'requestedThirdPartyPackages', discoveredPackages())->all())
        ->toBe(['vendor/two']);
});

it('selects every discovered package for --packages=all and none for --packages=none', function (): void {
    $command = makeThirdPartyInstallCommand(new BufferedOutput);

    $command->setInput(new ArrayInput(['--packages' => 'all'], $command->getDefinition()));
    expect(invokeThirdPartyMethod($command, 'requestedThirdPartyPackages', discoveredPackages())->all())
        ->toBe(['vendor/one', 'vendor/two']);

    $command->setInput(new ArrayInput(['--packages' => 'none'], $command->getDefinition()));
    expect(invokeThirdPartyMethod($command, 'requestedThirdPartyPackages', discoveredPackages())->all())
        ->toBe([]);
});

it('fails when --packages names a package that was not discovered', function (): void {
    $command = makeThirdPartyInstallCommand(new BufferedOutput);
    $command->setInput(new ArrayInput(['--packages' => 'vendor/one,vendor/typo'], $command->getDefinition()));

    expect(fn () => invokeThirdPartyMethod($command, 'requestedThirdPartyPackages', discoveredPackages()))
        ->toThrow(ManuallyFailedException::class, 'vendor/typo');
});

it('reports the third-party packages a non-interactive install left out', function (): void {
    $output = new BufferedOutput;
    $command = makeThirdPartyInstallCommand($output);

    invokeThirdPartyMethod($command, 'reportSkippedThirdPartyPackages', collect(['vendor/one', 'vendor/two']));

    expect($output->fetch())
        ->toContain('Skipped third-party guidelines/skills from: vendor/one, vendor/two.')
        ->toContain('--packages=vendor/one');
});

it('stays silent when a non-interactive install left nothing out', function (): void {
    $output = new BufferedOutput;
    $command = makeThirdPartyInstallCommand($output);

    invokeThirdPartyMethod($command, 'reportSkippedThirdPartyPackages', collect());

    expect($output->fetch())->toBe('');
});
