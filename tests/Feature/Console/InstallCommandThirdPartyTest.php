<?php

declare(strict_types=1);

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

function makeThirdPartyInstallCommand(Config $config, BufferedOutput $output): InstallCommand
{
    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('isInstalled')->andReturn(false);

    $sail = Mockery::mock(Sail::class);
    $sail->shouldReceive('isInstalled')->andReturn(false);
    $sail->shouldReceive('isActive')->andReturn(false);

    $terminal = Mockery::mock(Terminal::class);
    $terminal->shouldReceive('initDimensions');

    $command = new class(app(AgentsDetector::class), Mockery::mock(Cloud::class), $config, $nightwatch, $sail, $terminal) extends InstallCommand
    {
        /**
         * @param  Collection<string, ThirdPartyPackage>  $packages
         * @param  Collection<int, string>  $defaults
         * @return Collection<int, string>
         */
        public function packagesWithoutPrompt($packages, $defaults)
        {
            return $this->thirdPartyPackagesWithoutPrompt($packages, $defaults);
        }

        protected function displayBoostHeader(string $featureName, string $projectName, ?Theme $theme = null): void {}
    };

    $command->setLaravel(app());
    $command->setOutput(new OutputStyle(new ArrayInput([]), $output));

    return $command;
}

it('selects every discovered package when installing non-interactively without an existing config', function (): void {
    $output = new BufferedOutput;
    $command = makeThirdPartyInstallCommand(new Config, $output);

    $packages = collect([
        'vendor/one' => new ThirdPartyPackage('vendor/one', true, false),
        'vendor/two' => new ThirdPartyPackage('vendor/two', false, true),
    ]);

    expect($command->packagesWithoutPrompt($packages, collect())->all())
        ->toBe(['vendor/one', 'vendor/two'])
        ->and($output->fetch())->toContain('vendor/one, vendor/two');
});

it('keeps the packages an existing config already recorded when installing non-interactively', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);
    $config->setPackages(['vendor/one']);

    $output = new BufferedOutput;
    $command = makeThirdPartyInstallCommand($config, $output);

    $packages = collect([
        'vendor/one' => new ThirdPartyPackage('vendor/one', true, false),
        'vendor/two' => new ThirdPartyPackage('vendor/two', false, true),
    ]);

    expect($command->packagesWithoutPrompt($packages, collect(['vendor/one']))->all())
        ->toBe(['vendor/one']);
});

it('stays silent when a non-interactive install selects no packages', function (): void {
    $config = new Config;
    $config->setAgents(['claude_code']);

    $output = new BufferedOutput;
    $command = makeThirdPartyInstallCommand($config, $output);

    expect($command->packagesWithoutPrompt(collect(), collect())->all())->toBe([])
        ->and($output->fetch())->toBe('');
});

it('selects no third-party packages when --no-third-party is passed', function (): void {
    $output = new BufferedOutput;
    $command = makeThirdPartyInstallCommand(new Config, $output);

    $input = new ArrayInput(['--no-third-party' => true], $command->getDefinition());
    $input->setInteractive(false);
    $command->setInput($input);

    $method = new ReflectionMethod($command, 'selectThirdPartyPackages');

    expect($method->invoke($command)->all())->toBe([]);
});
