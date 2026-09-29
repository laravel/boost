<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Console\Enums\Theme;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Terminal;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    (new Config)->flush();
    config(['boost.enforce_tests' => false]);
});

afterEach(function (): void {
    (new Config)->flush();
    clearStagedPackages();
    Mockery::close();
});

/**
 * @return array{selected: array{features: array<int, string>, packages: array<int, string>, agents: array<int, string>}, output: string}
 */
function runPreselectionInstallCommand(array $parameters, ?string $answers = null): array
{
    $nightwatch = Mockery::mock(Nightwatch::class);
    $nightwatch->shouldReceive('isInstalled')->andReturn(false);

    $sail = Mockery::mock(Sail::class);
    $sail->shouldReceive('isInstalled')->andReturn(true);
    $sail->shouldReceive('isActive')->andReturn(false);

    $terminal = Mockery::mock(Terminal::class);
    $terminal->shouldReceive('initDimensions');

    $detector = Mockery::mock(AgentsDetector::class);
    $detector->shouldReceive('getAgents')->andReturn(app(AgentsDetector::class)->getAgents());
    $detector->shouldReceive('discoverSystemInstalledAgents')->andReturn([]);
    $detector->shouldReceive('discoverProjectInstalledAgents')->andReturn([]);

    $project = Mockery::mock(ProjectManager::class);
    mockProjectPackages($project, new PackageCollection([
        stagedPackage('acme/toolkit', 'guidelines'),
        stagedPackage('acme/ui-kit', 'skills'),
        stagedPackage('other/lib', 'guidelines'),
        stagedPackage('foo/bar', 'guidelines'),
    ]));

    $command = new class($detector, new Config, $nightwatch, $project, $sail, $terminal) extends InstallCommand
    {
        protected function displayBoostHeader(string $featureName, string $projectName, ?Theme $theme = null): void {}

        protected function performInstallation(): void {}

        protected function noteInferConventions(): void {}

        protected function outro(): void {}
    };

    $command->setLaravel(app());

    $input = new ArrayInput($parameters, $command->getDefinition());
    $input->setInteractive($answers !== null);

    if ($answers !== null) {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $answers);
        rewind($stream);
        $input->setStream($stream);
    }

    $output = new BufferedOutput;
    $command->run($input, $output);

    $reflection = new ReflectionClass(InstallCommand::class);

    /** @var Collection<int, Agent> $agents */
    $agents = $reflection->getProperty('selectedAgents')->getValue($command);

    return [
        'selected' => [
            'features' => $reflection->getProperty('selectedBoostFeatures')->getValue($command)->values()->all(),
            'packages' => $reflection->getProperty('selectedThirdPartyPackages')->getValue($command)->values()->all(),
            'agents' => $agents->map(fn (Agent $agent): string => $agent->name())->values()->all(),
        ],
        'output' => $output->fetch(),
    ];
}

it('uses the given agents instead of the configured ones in non-interactive mode', function (): void {
    (new Config)->setAgents(['cursor']);

    $result = runPreselectionInstallCommand(['--guidelines' => true, '--agent' => ['claude_code', 'junie']]);

    expect($result['selected']['agents'])->toEqualCanonicalizing(['claude_code', 'junie']);
})->skipOnWindows();

it('matches the given packages by name and wildcard in non-interactive mode', function (): void {
    (new Config)->setPackages(['foo/bar']);

    $result = runPreselectionInstallCommand(['--guidelines' => true, '--package' => ['acme/*', 'other/lib']]);

    expect($result['selected']['packages'])->toEqualCanonicalizing(['acme/toolkit', 'acme/ui-kit', 'other/lib']);
})->skipOnWindows();

it('uses the given integrations instead of the configured ones in non-interactive mode', function (): void {
    (new Config)->setCloud(true);

    $result = runPreselectionInstallCommand(['--skills' => true, '--integration' => ['sail']]);

    expect($result['selected']['features'])
        ->toContain('skills', 'sail')
        ->not->toContain('cloud');
})->skipOnWindows();

it('falls back to the configuration when no preselection options are given', function (): void {
    $config = new Config;
    $config->setAgents(['cursor']);
    $config->setPackages(['foo/bar']);
    $config->setCloud(true);

    $result = runPreselectionInstallCommand(['--skills' => true]);

    expect($result['selected']['agents'])->toBe(['cursor'])
        ->and($result['selected']['packages'])->toBe(['foo/bar'])
        ->and($result['selected']['features'])->toContain('cloud');
})->skipOnWindows();

it('warns about and ignores values that are not available', function (): void {
    $result = runPreselectionInstallCommand([
        '--mcp' => true,
        '--agent' => ['claude_code', 'unknown_agent'],
        '--integration' => ['cloud', 'sail'],
    ]);

    expect($result['selected']['agents'])->toBe(['claude_code'])
        ->and($result['selected']['features'])->toContain('sail')->not->toContain('cloud')
        ->and($result['output'])
        ->toContain('Ignoring --agent value that is not available: unknown_agent')
        ->toContain('Ignoring --integration value that is not available: cloud');
})->skipOnWindows();

it('warns about package patterns that match nothing', function (): void {
    $result = runPreselectionInstallCommand([
        '--guidelines' => true,
        '--package' => ['acme/toolkit', 'nope/*', 'missing/package'],
    ]);

    expect($result['selected']['packages'])->toBe(['acme/toolkit'])
        ->and($result['output'])->toContain('Ignoring --package values that are not available: nope/*, missing/package');
})->skipOnWindows();

it('uses the given values as defaults of the prompts in interactive mode', function (): void {
    (new Config)->setAgents(['cursor']);

    $result = runPreselectionInstallCommand([
        '--guidelines' => true,
        '--skills' => true,
        '--package' => ['acme/*'],
        '--integration' => ['cloud'],
        '--agent' => ['claude_code'],
    ], answers: str_repeat(PHP_EOL, 3)); // Accept the defaults of the package, integration and agent prompts.

    expect($result['selected']['packages'])->toEqualCanonicalizing(['acme/toolkit', 'acme/ui-kit'])
        ->and($result['selected']['features'])->toContain('cloud')->not->toContain('sail')
        ->and($result['selected']['agents'])->toBe(['claude_code']);
})->skipOnWindows();
