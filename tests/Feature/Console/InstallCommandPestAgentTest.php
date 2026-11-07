<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use JMac\Testing\Double;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\PestAgent;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->project = Double::for(ProjectManager::class, override: true)->instance();
    $this->app->instance(ProjectManager::class, $this->project);

    mockProjectPackages($this->project, new PackageCollection([
        rosterPackage('pestphp/pest', '5.0.3', dev: true)->setDirect(),
    ]));

    $this->command = $this->app->make(InstallCommand::class);
    $this->reflection = new ReflectionClass($this->command);
    $this->output = new BufferedOutput;

    $this->prepare = function (bool $interactive, array $features): void {
        $input = new ArrayInput([]);
        $input->setInteractive($interactive);

        $this->reflection->getProperty('input')->setValue($this->command, $input);
        $this->reflection->getProperty('output')->setValue($this->command, $this->output);
        $this->reflection->getProperty('selectedBoostFeatures')->setValue($this->command, collect($features));
        $this->reflection->getProperty('selectedThirdPartyPackages')->setValue($this->command, collect());
    };

    $this->selectedFeatures = fn (): array => $this->reflection->getProperty('selectedBoostFeatures')->getValue($this->command)->all();
    $this->selectedPackages = fn (): array => $this->reflection->getProperty('selectedThirdPartyPackages')->getValue($this->command)->all();
});

afterEach(function (): void {
    clearStagedPackages();
});

it('offers the pest agent integration without selecting it by default', function (): void {
    Prompt::fake([Key::ENTER]);

    ($this->prepare)(true, ['skills']);

    $this->reflection->getMethod('selectIntegrations')->invoke($this->command);

    Prompt::assertOutputContains('Pest Agent (installs pestphp/pest-plugin-agent)');

    expect(($this->selectedFeatures)())->not->toContain('pest_agent');
})->skipOnWindows()->skip(PHP_VERSION_ID < 80400, 'pestphp/pest-plugin-agent requires PHP 8.4');

it('selects the pest agent integration when chosen', function (): void {
    Prompt::fake([Key::DOWN, Key::SPACE, Key::ENTER]);

    ($this->prepare)(true, ['skills']);

    $this->reflection->getMethod('selectIntegrations')->invoke($this->command);

    expect(($this->selectedFeatures)())->toContain('pest_agent');
})->skipOnWindows()->skip(PHP_VERSION_ID < 80400, 'pestphp/pest-plugin-agent requires PHP 8.4');

it('never installs the pest agent plugin without interaction', function (): void {
    ($this->prepare)(false, ['skills']);

    $this->reflection->getMethod('selectIntegrations')->invoke($this->command);

    expect(($this->selectedFeatures)())->not->toContain('pest_agent');
});

it('does not offer the pest agent integration with only the mcp feature', function (): void {
    Prompt::fake();

    ($this->prepare)(true, ['mcp']);

    $this->reflection->getMethod('selectIntegrations')->invoke($this->command);

    Prompt::assertOutputDoesntContain('Pest Agent');

    expect(($this->selectedFeatures)())->not->toContain('pest_agent');
})->skipOnWindows();

it('discovers the plugin skills once composer installs it', function (): void {
    Prompt::fake();
    Process::fake(function () {
        $plugin = stagedPackage(PestAgent::PACKAGE, 'skills');
        stageSkill($plugin, 'pest-plugin-agent', 'One-shot Pest verification.');

        mockProjectPackages($this->project, new PackageCollection([
            rosterPackage('pestphp/pest', '5.0.3', dev: true)->setDirect(),
            $plugin,
        ]));

        return Process::result();
    });

    ($this->prepare)(true, ['skills', 'pest_agent']);

    $this->reflection->getMethod('installPestAgent')->invoke($this->command);

    $config = new GuidelineConfig;
    $config->aiGuidelines = ($this->selectedPackages)();

    expect(($this->selectedPackages)())->toBe([PestAgent::PACKAGE])
        ->and(ThirdPartyPackage::discover($this->project)->has(PestAgent::PACKAGE))->toBeTrue()
        ->and((new SkillComposer($this->project))->config($config)->skills()->get('pest-plugin-agent')?->package)->toBe(PestAgent::PACKAGE);
})->skipOnWindows();

it('reports why composer failed to install the plugin', function (): void {
    Prompt::fake();
    Process::fake(['*' => Process::result(errorOutput: "Problem 1\n  - pestphp/pest-plugin-agent requires php ^8.4", exitCode: 2)]);

    ($this->prepare)(true, ['skills', 'pest_agent']);

    $this->reflection->getMethod('installPestAgent')->invoke($this->command);

    expect(($this->selectedPackages)())->toBe([])
        ->and($this->output->fetch())
        ->toContain('Could not install pestphp/pest-plugin-agent')
        ->toContain('requires php ^8.4');
})->skipOnWindows();
