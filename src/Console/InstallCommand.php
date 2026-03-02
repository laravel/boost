<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Boost\Concerns\DisplayHelper;
use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\GuidelineWriter;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Install\McpWriter;
use Laravel\Boost\Install\Nightwatch;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Boost\Install\SkillWriter;
use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Terminal;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\grid;
use function Laravel\Prompts\multiselect;

class InstallCommand extends Command
{
    use DisplayHelper;

    protected $signature = 'boost:install
        {--guidelines : Install AI guidelines}
        {--skills : Install agent skills}
        {--mcp : Install MCP server configuration}';

    /** @var Collection<int, Agent> */
    private Collection $selectedAgents;

    /** @var Collection<int, string> */
    private Collection $selectedBoostFeatures;

    /** @var Collection<int, string> */
    private Collection $selectedThirdPartyPackages;

    private string $projectName;

    /** @var array<non-empty-string> */
    private array $systemInstalledAgents = [];

    /** @var array<non-empty-string> */
    private array $projectInstalledAgents = [];

    private bool $enforceTests = true;

    /** @var array<int, string> */
    private array $installedSkillNames = [];

    const MIN_TEST_COUNT = 6;

    public function __construct(
        private readonly AgentsDetector $agentsDetector,
        private readonly Config $config,
        private readonly Herd $herd,
        private readonly Nightwatch $nightwatch,
        private readonly Sail $sail,
        private readonly Terminal $terminal
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->terminal->initDimensions();
        $this->projectName = config('app.name');

        $this->displayBoostHeader('Install', $this->projectName);
        $this->discoverEnvironment();
        $this->collectInstallationPreferences();
        $this->performInstallation();
        $this->outro();

        return self::SUCCESS;
    }

    protected function discoverEnvironment(): void
    {
        if ($this->config->getAgents() !== []) {
            return;
        }

        $this->systemInstalledAgents = $this->agentsDetector->discoverSystemInstalledAgents();
        $this->projectInstalledAgents = $this->agentsDetector->discoverProjectInstalledAgents(base_path());
    }

    protected function collectInstallationPreferences(): void
    {
        $this->selectedBoostFeatures = $this->selectBoostFeatures();

        $this->selectedThirdPartyPackages = $this->selectedBoostFeatures->contains('guidelines') || $this->selectedBoostFeatures->contains('skills')
            ? $this->selectThirdPartyPackages()
            : collect();

        if ($this->selectedBoostFeatures->contains('mcp')) {
            $this->configureMcpOptions();
        }

        $this->selectedAgents = $this->selectAgents();
        $this->enforceTests = $this->selectedBoostFeatures->contains('guidelines') && $this->determineTestEnforcement();
    }

    protected function performInstallation(): void
    {
        if ($this->selectedBoostFeatures->contains('guidelines')) {
            $this->installGuidelines();
        }

        if ($this->selectedBoostFeatures->contains('skills')) {
            $this->installSkills();
        }

        if ($this->selectedBoostFeatures->contains('mcp')) {
            $this->installMcpServerConfig();
        }

        $this->storeConfig();
    }

    protected function outro(): void
    {
        $url = 'https://laravel.com/docs/boost';
        $link = $this->hyperlink($url, $url);
        $text = 'Enjoy the boost 🚀 Next steps: ';

        $this->displayOutro($text, $link, $this->terminal->cols());
    }

    /**
     * We shouldn't add an AI guideline enforcing test if they don't have a basic test setup.
     * This would likely just create headaches for them or be a waste of time as they
     * won't have the CI setup to make use of them anyway, so we're just wasting their
     * tokens/money by enforcing them.
     */
    protected function determineTestEnforcement(): bool
    {
        if (! file_exists(base_path('vendor/bin/phpunit'))) {
            return false;
        }

        $process = new Process([PHP_BINARY, 'artisan', 'test', '--list-tests'], base_path());
        $process->run();

        return Str::of($process->getOutput())
            ->trim()
            ->explode("\n")
            ->filter(fn ($line): bool => str_contains($line, '::'))
            ->count() >= self::MIN_TEST_COUNT;
    }

    /**
     * @return Collection<int, string>
     */
    protected function selectBoostFeatures(): Collection
    {
        $featureLabels = collect([
            'guidelines' => 'AI Guidelines',
            'skills' => 'Agent Skills',
            'mcp' => 'Boost MCP Server Configuration',
        ]);

        $explicit = $featureLabels->keys()->filter(fn ($feature) => $this->option($feature));

        if ($explicit->isNotEmpty()) {
            return $explicit->values();
        }

        $configValues = collect([
            'guidelines' => $this->config->getGuidelines(),
            'skills' => $this->config->hasSkills(),
            'mcp' => $this->config->getMcp(),
        ]);

        $defaults = $configValues->filter()->keys()->whenEmpty(fn () => $featureLabels->keys());

        return collect(multiselect(
            label: 'Which Boost features would you like to configure?',
            options: $featureLabels->all(),
            default: $defaults->all(),
            required: true,
            hint: 'This will override the current guidelines, skills, and MCP configuration',
        ));
    }

    protected function configureMcpOptions(): void
    {
        if ($this->sail->isInstalled() && ($this->sail->isActive() || $this->shouldConfigureSail())) {
            $this->selectedBoostFeatures->push('sail');
        }

        if ($this->herd->isMcpAvailable() && $this->shouldConfigureHerdMcp()) {
            $this->selectedBoostFeatures->push('herd_mcp');
        }

        if ($this->nightwatch->isInstalled() && $this->shouldConfigureNightwatchMcp()) {
            $this->selectedBoostFeatures->push('nightwatch_mcp');
        }
    }

    protected function shouldConfigureSail(): bool
    {
        return confirm(
            label: 'Laravel Sail detected. Configure Boost MCP to use Sail?',
            default: $this->config->getSail(),
            hint: 'This will configure the MCP server to run through Sail. Note: Sail must be running to use Boost MCP',
        );
    }

    protected function shouldConfigureHerdMcp(): bool
    {
        return confirm(
            label: 'Would you like to install Herd MCP alongside Boost MCP?',
            default: $this->config->getHerdMcp(),
            hint: 'The Herd MCP provides additional tools like browser logs, which can help AI understand issues better',
        );
    }

    protected function shouldConfigureNightwatchMcp(): bool
    {
        return confirm(
            label: 'Would you like to install Nightwatch MCP alongside Boost MCP?',
            default: $this->config->getNightwatchMcp(),
            hint: 'The Nightwatch MCP provides tools for browsing issues, viewing stack traces, and managing application errors',
        );
    }

    /**
     * @return Collection<int, string>
     */
    protected function selectThirdPartyPackages(): Collection
    {
        $packages = ThirdPartyPackage::discover();

        if ($packages->isEmpty()) {
            return collect();
        }

        return collect(multiselect(
            label: 'Which third-party AI guidelines/skills would you like to install?',
            options: $packages->mapWithKeys(fn (ThirdPartyPackage $pkg, string $name): array => [
                $name => $pkg->displayLabel(),
            ])->toArray(),
            default: collect($this->config->getPackages())
                ->filter(fn (string $name) => $packages->has($name))
                ->values(),
            scroll: 10,
            hint: 'You can add or remove them later by running this command again',
        ));
    }

    /**
     * @return Collection<int, Agent>
     */
    protected function selectAgents(): Collection
    {
        $allAgents = $this->agentsDetector->getAgents();

        if ($allAgents->isEmpty()) {
            return collect();
        }

        $featureInterfaces = [
            'guidelines' => SupportsGuidelines::class,
            'skills' => SupportsSkills::class,
            'mcp' => SupportsMcp::class,
        ];

        $filteredAgents = $allAgents->filter(
            fn (Agent $agent): bool => $this->selectedBoostFeatures->contains(
                fn ($feature): bool => isset($featureInterfaces[$feature]) && $agent instanceof $featureInterfaces[$feature])
        )->keyBy(fn (Agent $agent): string => $agent->name());

        if ($filteredAgents->isEmpty()) {
            return collect();
        }

        $options = $filteredAgents
            ->mapWithKeys(fn (Agent $agent): array => [$agent->name() => $agent->displayName()])
            ->sort();

        $defaults = collect($this->config->getAgents())
            ->filter(fn (string $name) => $filteredAgents->has($name))
            ->whenEmpty(fn () => collect([...$this->projectInstalledAgents, ...$this->systemInstalledAgents])
                ->unique()
                ->filter(fn (string $name) => $filteredAgents->has($name))
            )
            ->values();

        $selected = multiselect(
            label: 'Which AI agents would you like to configure?',
            options: $options->all(),
            default: $defaults->all(),
            scroll: $options->count(),
            required: true,
        );

        return collect($selected)
            ->map(fn (string $name) => $filteredAgents->get($name))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, Agent&SupportsMcp>
     */
    protected function agentsWithMcp(): Collection
    {
        return $this->selectedAgents->filter(fn (Agent $a): bool => $a instanceof SupportsMcp);
    }

    /**
     * @return Collection<int, Agent&SupportsGuidelines>
     */
    protected function agentsWithGuidelines(): Collection
    {
        return $this->selectedAgents->filter(fn (Agent $a): bool => $a instanceof SupportsGuidelines);
    }

    /**
     * @return Collection<int, Agent&SupportsSkills>
     */
    protected function agentsWithSkills(): Collection
    {
        return $this->selectedAgents->filter(fn (Agent $a): bool => $a instanceof SupportsSkills);
    }

    protected function installGuidelines(): void
    {
        $guidelinesAgents = $this->agentsWithGuidelines();
        $composer = app(GuidelineComposer::class)->config($this->buildGuidelineConfig());
        $guidelines = $composer->guidelines();
        $composedAiGuidelines = $composer->compose();

        $this->installFeature(
            agents: $guidelinesAgents,
            emptyMessage: 'No agents are selected for guideline installation.',
            headerMessage: sprintf('Adding %d guidelines to your selected agents', $guidelines->count()),
            nameResolver: fn (Agent $agent): string => $agent->displayName(),
            processor: fn (Agent&SupportsGuidelines $agent): int => (new GuidelineWriter($agent))->write($composedAiGuidelines),
            featureName: 'guidelines',
            beforeProcess: fn () => grid($guidelines->map(fn ($guideline, string $key): string => $key.($guideline['custom'] ? '*' : ''))->sort()->values()->toArray()),
            withDelay: true,
        );
    }

    protected function installSkills(): void
    {
        $skillsAgents = $this->agentsWithSkills();
        $skillsComposer = app(SkillComposer::class)->config($this->buildGuidelineConfig());
        $skills = $skillsComposer->skills();

        $this->installedSkillNames = $skills->keys()->toArray();

        /** @var Collection<int, SupportsSkills&Agent> $skillsAgents */
        $this->installFeature(
            agents: $skillsAgents,
            emptyMessage: 'No agents are selected for skill installation.',
            headerMessage: sprintf('Syncing %d skills for skills-capable agents', $skills->count()),
            nameResolver: fn (SupportsSkills&Agent $agent): string => $agent->displayName(),
            processor: fn (SupportsSkills&Agent $agent): array => (new SkillWriter($agent))->sync($skills, $this->config->getSkills()),
            featureName: 'skills',
            beforeProcess: $skills->isNotEmpty()
                ? fn () => grid($skills->map(fn (Skill $skill): string => $skill->displayName())->sort()->values()->toArray())
                : null,
        );
    }

    protected function buildGuidelineConfig(): GuidelineConfig
    {
        $guidelineConfig = new GuidelineConfig;
        $guidelineConfig->enforceTests = $this->enforceTests;
        $guidelineConfig->hasAnApi = false;
        $guidelineConfig->aiGuidelines = $this->selectedThirdPartyPackages->values()->toArray();
        $guidelineConfig->usesSail = $this->shouldUseSail();
        $guidelineConfig->hasSkills = $this->selectedBoostFeatures->contains('skills');

        return $guidelineConfig;
    }

    protected function storeConfig(): void
    {
        $explicitMode = $this->isExplicitFlagMode();

        if (! $explicitMode) {
            $this->config->flush();
            $this->config->setAgents($this->selectedAgents->map(fn (Agent $agent): string => $agent->name())->values()->toArray());
            $this->config->setPackages($this->selectedThirdPartyPackages->values()->toArray());
        } elseif ($this->selectedBoostFeatures->contains('guidelines') || $this->selectedBoostFeatures->contains('skills')) {
            $this->config->setPackages($this->selectedThirdPartyPackages->values()->toArray());
        }

        if ($this->selectedBoostFeatures->contains('guidelines')) {
            $this->config->setGuidelines(true);
        }

        if ($this->selectedBoostFeatures->contains('skills')) {
            $this->config->setSkills($this->installedSkillNames);
        }

        if ($this->selectedBoostFeatures->contains('mcp')) {
            $this->config->setMcp(true);
            $this->config->setSail($this->shouldUseSail());
            $this->config->setHerdMcp($this->shouldInstallHerdMcp());
            $this->config->setNightwatchMcp($this->shouldInstallNightwatchMcp());
        }
    }

    protected function shouldInstallHerdMcp(): bool
    {
        return $this->selectedBoostFeatures->contains('herd_mcp');
    }

    protected function shouldInstallNightwatchMcp(): bool
    {
        return $this->selectedBoostFeatures->contains('nightwatch_mcp');
    }

    protected function shouldUseSail(): bool
    {
        if ($this->selectedBoostFeatures->contains('mcp')) {
            return $this->selectedBoostFeatures->contains('sail');
        }

        return $this->config->getSail();
    }

    protected function isExplicitFlagMode(): bool
    {
        if ($this->option('guidelines')) {
            return true;
        }

        if ($this->option('skills')) {
            return true;
        }

        return (bool) $this->option('mcp');
    }

    protected function installMcpServerConfig(): void
    {
        $this->installFeature(
            agents: $this->agentsWithMcp(),
            emptyMessage: 'No agents are selected for MCP installation.',
            headerMessage: 'Installing MCP servers to your selected Agents',
            nameResolver: fn (Agent $agent): string => $agent->displayName(),
            processor: fn (Agent&SupportsMcp $agent): int => (new McpWriter($agent))->write(
                $this->shouldUseSail() ? $this->sail : null,
                $this->shouldInstallHerdMcp() ? $this->herd : null,
                $this->shouldInstallNightwatchMcp() ? $this->nightwatch : null
            ),
            featureName: 'MCP servers',
            withDelay: true,
        );
    }

    /**
     * @template T
     *
     * @param  Collection<int, T>  $agents
     * @param  callable(T): string  $nameResolver
     * @param  callable(T): mixed  $processor
     * @param  ?callable(): void  $beforeProcess
     */
    protected function installFeature(
        Collection $agents,
        string $emptyMessage,
        string $headerMessage,
        callable $nameResolver,
        callable $processor,
        string $featureName,
        ?callable $beforeProcess = null,
        bool $withDelay = false,
    ): void {
        if ($agents->isEmpty()) {
            $this->info($emptyMessage);

            return;
        }

        $this->newLine();
        $this->info($headerMessage);

        if ($beforeProcess !== null) {
            $beforeProcess();
        }

        $this->newLine();

        if ($withDelay) {
            usleep(750000);
        }

        $failed = [];
        $nameMap = $agents->map(fn ($agent): string => $nameResolver($agent));
        $longestName = $nameMap->map(fn (string $name) => Str::length($name))->max() ?? 0;

        foreach ($agents as $index => $agent) {
            $name = $nameMap[$index];
            $this->output->write('  '.str_pad($name, $longestName).'... ');

            try {
                $processor($agent);
                $this->line($this->green('✓'));
            } catch (Exception $e) {
                $failed[$name] = $e->getMessage();
                $this->line($this->red('✗'));
            }
        }

        if ($failed !== []) {
            $this->newLine();
            $this->error(sprintf('✗ Failed to install %s to %d agent%s:',
                $featureName,
                count($failed),
                count($failed) === 1 ? '' : 's'
            ));

            foreach ($failed as $agentName => $error) {
                $this->line("  - {$agentName}: {$error}");
            }
        }

        $this->newLine();
    }
}
