<?php

declare(strict_types=1);

use Illuminate\Console\OutputStyle;
use Laravel\Boost\Console\Enums\Theme;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Cloud;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

beforeEach(function (): void {
    (new Config)->flush();
    config(['boost.enforce_tests' => false]);
});

afterEach(function (): void {
    (new Config)->flush();
    Mockery::close();
});

function makeTestInstallCommand(Config $config, ?AgentsDetector $detector = null): InstallCommand
{
    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('isInstalled')->andReturn(false);

    $sail = Mockery::mock(Sail::class);
    $sail->shouldReceive('isInstalled')->andReturn(false);
    $sail->shouldReceive('isActive')->andReturn(false);

    $terminal = Mockery::mock(Terminal::class);
    $terminal->shouldReceive('initDimensions');

    return new class(
        $detector ?? app(AgentsDetector::class),
        Mockery::mock(Cloud::class),
        $config,
        $nightwatch,
        $sail,
        $terminal,
    ) extends InstallCommand {
        protected function displayBoostHeader(string $featureName, string $projectName, ?Theme $theme = null): void {}

        protected function performInstallation(): void {}

        protected function outro(): void {}
    };
}

it('does not throw when no agents are saved and none are auto-detected in non-interactive mode', function (): void {
    $config = new Config;

    $detector = Mockery::mock(AgentsDetector::class);
    $detector->shouldReceive('getAgents')->andReturn(app(AgentsDetector::class)->getAgents());
    $detector->shouldReceive('discoverSystemInstalledAgents')->andReturn([]);
    $detector->shouldReceive('discoverProjectInstalledAgents')->andReturn([]);

    $command = makeTestInstallCommand($config, $detector);

    $input = new ArrayInput(['--guidelines' => true], $command->getDefinition());
    $input->setInteractive(false);
    $command->setLaravel(app());

    expect($command->run($input, new NullOutput))->toBe(0);
})->skipOnWindows();

it('silently drops stale agents no longer in the available list in non-interactive mode', function (): void {
    $config = new Config;
    $config->setAgents(['gemini']);

    $command = makeTestInstallCommand($config);

    $input = new ArrayInput(['--guidelines' => true], $command->getDefinition());
    $input->setInteractive(false);
    $command->setLaravel(app());

    expect($command->run($input, new NullOutput))->toBe(0);
})->skipOnWindows();
