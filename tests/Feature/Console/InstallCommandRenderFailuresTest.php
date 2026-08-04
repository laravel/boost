<?php

declare(strict_types=1);

use Laravel\Boost\Console\Enums\Theme;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Cloud;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Support\Config;
use Laravel\Boost\Support\RenderFailures;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    (new Config)->flush();
    config(['boost.enforce_tests' => false]);
});

afterEach(function (): void {
    (new Config)->flush();
    Mockery::close();
});

function runInstallCommandWithFailures(array $failedPaths): string
{
    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('isInstalled')->andReturn(false);

    $sail = Mockery::mock(Sail::class);
    $sail->shouldReceive('isInstalled')->andReturn(false);
    $sail->shouldReceive('isActive')->andReturn(false);

    $terminal = Mockery::mock(Terminal::class);
    $terminal->shouldReceive('initDimensions');

    $detector = Mockery::mock(AgentsDetector::class);
    $detector->shouldReceive('getAgents')->andReturn(app(AgentsDetector::class)->getAgents());
    $detector->shouldReceive('discoverSystemInstalledAgents')->andReturn([]);
    $detector->shouldReceive('discoverProjectInstalledAgents')->andReturn([]);

    $command = new class($detector, Mockery::mock(Cloud::class), new Config, $nightwatch, $sail, $terminal) extends InstallCommand
    {
        public array $failedPaths = [];

        protected function displayBoostHeader(string $featureName, string $projectName, ?Theme $theme = null): void {}

        protected function performInstallation(): void
        {
            foreach ($this->failedPaths as $path) {
                app(RenderFailures::class)->record($path);
            }
        }

        protected function outro(): void {}
    };

    $command->failedPaths = $failedPaths;
    $command->setLaravel(app());

    $input = new ArrayInput(['--guidelines' => true], $command->getDefinition());
    $input->setInteractive(false);

    $output = new BufferedOutput;
    $command->run($input, $output);

    return $output->fetch();
}

it('tells the user which packages to update when their guidelines could not be rendered', function (): void {
    $output = runInstallCommandWithFailures([
        base_path('vendor/inertiajs/inertia-laravel/resources/boost/guidelines/core.blade.php'),
        base_path('vendor/laravel/wayfinder/resources/boost/guidelines/core.blade.php'),
    ]);

    expect($output)
        ->toContain('Skipped 2 files that could not be rendered')
        ->toContain('vendor/inertiajs/inertia-laravel/resources/boost/guidelines/core.blade.php')
        ->toContain('composer update inertiajs/inertia-laravel laravel/wayfinder');
})->skipOnWindows();

it('does not suggest a package update for files outside the vendor directory', function (): void {
    $output = runInstallCommandWithFailures(['/app/.ai/guidelines/custom.blade.php']);

    expect($output)
        ->toContain('Skipped 1 file that could not be rendered')
        ->toContain('.ai/guidelines/custom.blade.php')
        ->not->toContain('composer update');
})->skipOnWindows();

it('stays quiet when everything rendered', function (): void {
    expect(runInstallCommandWithFailures([]))->not->toContain('could not be rendered');
})->skipOnWindows();
