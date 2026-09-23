<?php

declare(strict_types=1);

use JMac\Testing\Double;
use Laravel\Boost\Console\Enums\Theme;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Terminal;
use Laravel\Roster\ProjectManager;
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
    $nightwatch = Double::for(Nightwatch::class);
    $nightwatch->allows('isInstalled')->returns(false);

    $sail = Double::for(Sail::class);
    $sail->allows('isInstalled')->returns(false);
    $sail->allows('isActive')->returns(false);

    $terminal = Double::for(Terminal::class);
    $terminal->allows('initDimensions');

    return new class($detector ?? app(AgentsDetector::class), $config, $nightwatch, app(ProjectManager::class), $sail, $terminal) extends InstallCommand
    {
        protected function displayBoostHeader(string $featureName, string $projectName, ?Theme $theme = null): void {}

        protected function performInstallation(): void {}

        protected function outro(): void {}
    };
}

it('does not throw when no agents are saved and none are auto-detected in non-interactive mode', function (): void {
    $config = new Config;

    $detector = Double::for(AgentsDetector::class);
    $detector->allows('getAgents')->returns(app(AgentsDetector::class)->getAgents());
    $detector->allows('discoverSystemInstalledAgents')->returns([]);
    $detector->allows('discoverProjectInstalledAgents')->returns([]);

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
