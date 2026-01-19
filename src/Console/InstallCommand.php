<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Boost\Contracts\SupportGuidelines;
use Laravel\Boost\Contracts\SupportMCP;
use Laravel\Boost\Contracts\SupportSkills;
use Laravel\Boost\Install\Agent\Agent;
use Laravel\Boost\Install\AgentDetector;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\GuidelineWriter;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Boost\Install\SkillWriter;
use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Terminal;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\grid;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;

class InstallCommand extends Command
{
    use Colors;

    protected $signature = 'boost:install {--ignore-guidelines : Skip installing AI guidelines} {--ignore-mcp : Skip installing MCP server configuration}';

    private AgentDetector $agentDetector;

    private Herd $herd;

    private Sail $sail;

    private Terminal $terminal;

    /** @var Collection<int, SupportGuidelines> */
    private Collection $selectedTargetAgents;

    /** @var Collection<int, SupportMCP> */
    private Collection $selectedTargetMcpClient;

    /** @var Collection<int, string> */
    private Collection $selectedBoostFeatures;

    /** @var Collection<int, string> */
    private Collection $selectedAiGuidelines;

    private string $projectName;

    /** @var array<non-empty-string> */
    private array $systemInstalledAgents = [];

    /** @var array<non-empty-string> */
    private array $projectInstalledAgents = [];

    private bool $enforceTests = true;

    const MIN_TEST_COUNT = 6;

    private string $greenTick;

    private string $redCross;

    private bool $installGuidelines;

    private bool $installMcpConfig;

    public function __construct(protected Config $config)
    {
        parent::__construct();
    }

    public function handle(
        AgentDetector $agentDetector,
        Herd $herd,
        Sail $sail,
        Terminal $terminal,
    ): int {
        $this->installGuidelines = ! $this->option('ignore-guidelines');
        $this->installMcpConfig = ! $this->option('ignore-mcp');

        if (! $this->installGuidelines && ! $this->installMcpConfig) {
            $this->error('You cannot ignore both guidelines and MCP config. Please select at least one option to proceed.');

            return self::FAILURE;
        }

        $this->bootstrap($agentDetector, $herd, $sail, $terminal);
        $this->displayBoostHeader();
        $this->discoverEnvironment();
        $this->collectInstallationPreferences();
        $this->performInstallation();
        $this->outro();

        return self::SUCCESS;
    }

    protected function bootstrap(AgentDetector $agentDetector, Herd $herd, Sail $sail, Terminal $terminal): void
    {
        $this->agentDetector = $agentDetector;
        $this->herd = $herd;
        $this->sail = $sail;
        $this->terminal = $terminal;

        $this->terminal->initDimensions();

        $this->greenTick = $this->green('✓');
        $this->redCross = $this->red('✗');

        $this->selectedTargetAgents = collect();
        $this->selectedTargetMcpClient = collect();

        $this->projectName = config('app.name');
    }

    protected function displayBoostHeader(): void
    {
        note($this->boostLogo());
        intro('✦ Laravel Boost :: Install :: We Must Ship ✦');
        note("Let's give {$this->bgYellow($this->black($this->bold($this->projectName)))} a Boost");
    }

    protected function boostLogo(): string
    {
        return
         <<<'HEADER'
        ██████╗   ██████╗   ██████╗  ███████╗ ████████╗
        ██╔══██╗ ██╔═══██╗ ██╔═══██╗ ██╔════╝ ╚══██╔══╝
        ██████╔╝ ██║   ██║ ██║   ██║ ███████╗    ██║
        ██╔══██╗ ██║   ██║ ██║   ██║ ╚════██║    ██║
        ██████╔╝ ╚██████╔╝ ╚██████╔╝ ███████║    ██║
        ╚═════╝   ╚═════╝   ╚═════╝  ╚══════╝    ╚═╝
        HEADER;
    }

    protected function discoverEnvironment(): void
    {
        $this->systemInstalledAgents = $this->agentDetector->discoverSystemInstalledAgents();
        $this->projectInstalledAgents = $this->agentDetector->discoverProjectInstalledAgents(base_path());
    }

    protected function collectInstallationPreferences(): void
    {
        $this->selectedBoostFeatures = $this->selectBoostFeatures();
        $this->selectedAiGuidelines = $this->selectThirdPartyPackages();
        $this->selectedTargetMcpClient = $this->selectTargetMcpClients();
        $this->selectedTargetAgents = $this->selectTargetAgents();
        $this->enforceTests = $this->determineTestEnforcement();
    }

    protected function performInstallation(): void
    {
        if ($this->installGuidelines) {
            $this->installGuidelines();
        }

        usleep(750000);

        if ($this->installMcpConfig && $this->selectedTargetMcpClient->isNotEmpty()) {
            $this->installMcpServerConfig();
        }
    }

    protected function outro(): void
    {
        $label = 'https://boost.laravel.com/installed';

        $ideNames = $this->selectedTargetMcpClient->map(fn (SupportMCP $mcpClient): string => 'i:'.$mcpClient->name())
            ->toArray();
        $agentNames = $this->selectedTargetAgents->map(fn (SupportGuidelines $agent): string => 'a:'.$agent->name())->toArray();
        $boostFeatures = $this->selectedBoostFeatures->map(fn ($feature): string => 'b:'.$feature)->toArray();

        $guidelines = [];

        $guidelines[] = 'g:ai';

        if ($this->shouldInstallStyleGuidelines()) {
            $guidelines[] = 'g:style';
        }

        $allData = array_merge($ideNames, $agentNames, $boostFeatures, $guidelines);
        $installData = base64_encode(implode(',', $allData));

        $link = $this->hyperlink($label, 'https://boost.laravel.com/installed/?d='.$installData);

        $text = 'Enjoy the boost 🚀 Next steps: ';
        $paddingLength = (int) (floor(($this->terminal->cols() - mb_strlen($text.$label)) / 2)) - 2;

        $this->output->write([
            "\033[42m\033[2K".str_repeat(' ', max(0, $paddingLength)),
            $this->black($this->bold($text.$link)).$this->reset(PHP_EOL).$this->reset(PHP_EOL),
        ]);
    }

    protected function hyperlink(string $label, string $url): string
    {
        return "\033]8;;{$url}\007{$label}\033]8;;\033\\";
    }

    /**
     * We shouldn't add an AI guideline enforcing tests if they don't have a basic test setup.
     * This would likely just create headaches for them or be a waste of time as they
     * won't have the CI setup to make use of them anyway, so we're just wasting their
     * tokens/money by enforcing them.
     */
    protected function determineTestEnforcement(): bool
    {
        if (! $this->installGuidelines) {
            return false;
        }

        $hasMinimumTests = false;

        if (file_exists(base_path('vendor/bin/phpunit'))) {
            $process = new Process([PHP_BINARY, 'artisan', 'test', '--list-tests'], base_path());
            $process->run();

            /** Count the number of tests - they'll always have :: between the filename and test name */
            $hasMinimumTests = Str::of($process->getOutput())
                ->trim()
                ->explode("\n")
                ->filter(fn ($line): bool => str_contains($line, '::'))
                ->count() >= self::MIN_TEST_COUNT;
        }

        return $hasMinimumTests;
    }

    /**
     * @return Collection<int, string>
     */
    protected function selectBoostFeatures(): Collection
    {
        if (! $this->installMcpConfig) {
            return collect();
        }

        $features = collect(['mcp_server', 'ai_guidelines']);

        if ($this->herd->isMcpAvailable() && $this->shouldConfigureHerdMcp()) {
            $features->push('herd_mcp');
        }

        if ($this->sail->isInstalled() && ($this->sail->isActive() || $this->shouldConfigureSail())) {
            $features->push('sail');
        }

        return $features;
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

    /**
     * @return Collection<int, string>
     */
    protected function selectThirdPartyPackages(): Collection
    {
        if (! $this->installGuidelines) {
            return collect();
        }

        $packages = ThirdPartyPackage::discover(app(GuidelineComposer::class));

        if ($packages->isEmpty()) {
            return collect();
        }

        return collect(multiselect(
            label: 'Which third-party AI guidelines/skills do you want to install?',
            options: $packages->mapWithKeys(fn (ThirdPartyPackage $pkg, string $name): array => [
                $name => $pkg->displayLabel(),
            ])->toArray(),
            default: collect($this->config->getGuidelines()),
            scroll: 10,
            hint: 'You can add or remove them later by running this command again',
        ));
    }

    /**
     * @return Collection<int, Agent>
     */
    protected function selectTargetMcpClients(): Collection
    {
        if (! $this->installMcpConfig) {
            return collect();
        }

        return $this->selectAgents(
            SupportMCP::class,
            sprintf('Which code editors do you use to work on %s?', $this->projectName),
            $this->config->getEditors(),
        );
    }

    /**
     * @return Collection<int, Agent>
     */
    protected function selectTargetAgents(): Collection
    {
        if (! $this->installGuidelines) {
            return collect();
        }

        $defaults = $this->config->getAgents();

        if ($this->selectedTargetMcpClient->isNotEmpty()) {
            $defaults = $this->selectedTargetMcpClient
                ->filter(fn (SupportMCP $client): bool => $client instanceof SupportGuidelines)
                ->map(fn (SupportMCP $client): string => $client->name())
                ->values()
                ->toArray();
        }

        return $this->selectAgents(
            SupportGuidelines::class,
            sprintf('Which agents need AI guidelines for %s?', $this->projectName),
            $defaults,
        );
    }

    /**
     * Get configuration settings for contract-specific selection behavior.
     *
     * @return array{required: bool, displayMethod: string}
     */
    protected function getSelectionConfig(string $contractClass): array
    {
        return match ($contractClass) {
            SupportGuidelines::class => ['required' => false, 'displayMethod' => 'displayName'],
            SupportMCP::class => ['required' => true, 'displayMethod' => 'displayName'],
            default => throw new InvalidArgumentException("Unsupported contract class: {$contractClass}"),
        };
    }

    /**
     * @param  array<int, string>  $defaults
     * @return Collection<int, Agent>
     */
    protected function selectAgents(string $contractClass, string $label, array $defaults): Collection
    {
        $allAgents = $this->agentDetector->getAgents();
        $config = $this->getSelectionConfig($contractClass);

        $availableAgents = $allAgents->filter(fn (Agent $agent): bool => $agent instanceof $contractClass);

        if ($availableAgents->isEmpty()) {
            return collect();
        }

        $options = $availableAgents->mapWithKeys(function (Agent $agent) use ($config): array {
            $displayMethod = $config['displayMethod'];
            $displayText = $agent->{$displayMethod}();

            return [$agent->name() => $displayText];
        })->sort();

        $installedAgentNames = array_unique(array_merge(
            $this->projectInstalledAgents,
            $this->systemInstalledAgents
        ));

        $detectedDefaults = [];

        if ($defaults === []) {
            foreach ($installedAgentNames as $agentKey) {
                $matchingAgent = $availableAgents->first(fn (Agent $agent): bool => strtolower((string) $agentKey) === strtolower($agent->name()));
                if ($matchingAgent) {
                    $detectedDefaults[] = $matchingAgent->name();
                }
            }
        }

        $selectedAgents = collect(multiselect(
            label: $label,
            options: $options->toArray(),
            default: $defaults === [] ? $detectedDefaults : $defaults,
            scroll: $options->count(),
            required: $config['required'],
            hint: $defaults === [] || $detectedDefaults === [] ? '' : sprintf('Auto-detected %s for you',
                Arr::join(array_map(function ($className) use ($availableAgents, $config) {
                    $agent = $availableAgents->first(fn ($agent): bool => $agent->name() === $className);
                    $displayMethod = $config['displayMethod'];

                    return $agent->{$displayMethod}();
                }, $detectedDefaults), ', ', ' & ')
            )
        ))->sort();

        return $selectedAgents->map(
            fn (string $name) => $availableAgents->first(fn ($agent): bool => $agent->name() === $name),
        )->filter()->values();
    }

    protected function installGuidelines(): void
    {
        if ($this->selectedTargetAgents->isEmpty()) {
            $this->info(' No agents selected for guideline installation.');

            return;
        }

        $guidelineConfig = new GuidelineConfig;
        $guidelineConfig->enforceTests = $this->enforceTests;
        $guidelineConfig->laravelStyle = $this->shouldInstallStyleGuidelines();
        $guidelineConfig->caresAboutLocalization = $this->detectLocalization();
        $guidelineConfig->hasAnApi = false;
        $guidelineConfig->aiGuidelines = $this->selectedAiGuidelines->values()->toArray();
        $guidelineConfig->usesSail = $this->shouldUseSail();

        $composer = app(GuidelineComposer::class)->config($guidelineConfig);
        $guidelines = $composer->guidelines();

        $this->newLine();
        $this->info(sprintf(' Adding %d guidelines to your selected agents', $guidelines->count()));
        grid($guidelines->map(fn ($guideline, string $key): string => $key.($guideline['custom'] ? '*' : ''))->sort()->values()->toArray());
        $this->newLine();
        usleep(750000);

        $composedAiGuidelines = $composer->compose();

        $this->processWithProgress(
            $this->selectedTargetAgents,
            fn (SupportGuidelines $agent): string => $agent->displayName(),
            fn (SupportGuidelines $agent): int => (new GuidelineWriter($agent))->write($composedAiGuidelines),
            'guidelines',
        );

        $this->newLine();

        $skillComposer = app(SkillComposer::class)->config($guidelineConfig);
        $this->installSkills($skillComposer);

        if ($this->installMcpConfig) {
            $this->config->setSail(
                $this->shouldUseSail()
            );

            $this->config->setHerdMcp(
                $this->shouldInstallHerdMcp()
            );

            $this->config->setEditors(
                $this->selectedTargetMcpClient->map(fn (SupportMCP $mcpClient): string => $mcpClient->name())->values()->toArray()
            );
        }

        $this->config->setAgents(
            $this->selectedTargetAgents->map(fn (SupportGuidelines $agent): string => $agent->name())->values()->toArray()
        );

        $this->config->setGuidelines(
            $this->selectedAiGuidelines->values()->toArray()
        );
    }

    protected function installSkills(SkillComposer $skillComposer): void
    {
        $skillsAgents = $this->selectedTargetAgents
            ->filter(fn ($agent): bool => $agent instanceof SupportSkills);

        $skills = $skillComposer->skills();

        if ($skillsAgents->isEmpty() || $skills->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->info(sprintf(' Installing %d skills for skills-capable agents', $skills->count()));
        grid($skills->map(fn (Skill $skill): string => $skill->displayName())->sort()->values()->toArray());
        $this->newLine();

        /** @var Collection<int, SupportSkills&SupportGuidelines> $skillsAgents */
        $this->processWithProgress(
            $skillsAgents,
            fn (SupportSkills&SupportGuidelines $agent): string => $agent->displayName(),
            fn (SupportSkills&SupportGuidelines $agent): array => (new SkillWriter($agent))->writeAll($skills),
            'skills',
        );

        $this->newLine();
    }

    protected function shouldInstallStyleGuidelines(): bool
    {
        return false;
    }

    protected function shouldInstallHerdMcp(): bool
    {
        return $this->selectedBoostFeatures->contains('herd_mcp');
    }

    protected function shouldUseSail(): bool
    {
        return $this->selectedBoostFeatures->isEmpty()
            ? $this->config->getSail()
            : $this->selectedBoostFeatures->contains('sail');
    }

    protected function buildMcpCommand(SupportMCP $mcpClient): array
    {
        $serverName = 'laravel-boost';

        if ($this->shouldUseSail()) {
            return $this->sail->buildMcpCommand($serverName);
        }

        $inWsl = $this->isRunningInWsl();

        return array_filter([
            $serverName,
            $inWsl ? 'wsl.exe' : false,
            $mcpClient->getPhpPath($inWsl),
            $mcpClient->getArtisanPath($inWsl),
            'boost:mcp',
        ]);
    }

    protected function installMcpServerConfig(): void
    {
        if ($this->selectedTargetMcpClient->isEmpty()) {
            $this->info('No agents selected for guideline installation.');

            return;
        }

        $this->newLine();
        $this->info(' Installing MCP servers to your selected IDEs');
        $this->newLine();

        usleep(750000);

        $failed = [];
        $longestIdeName = max(
            1,
            ...$this->selectedTargetMcpClient->map(
                fn (SupportMCP $agent) => Str::length($agent->name())
            )->toArray()
        );

        foreach ($this->selectedTargetMcpClient as $agent) {
            $agentName = $agent->displayName();
            $agentDisplayName = str_pad((string) $agentName, $longestIdeName);
            $this->output->write("  {$agentDisplayName}... ");
            $results = [];

            $mcp = $this->buildMcpCommand($agent);

            try {
                $result = $agent->installMcp(
                    array_shift($mcp),
                    array_shift($mcp),
                    $mcp
                );

                if ($result) {
                    $results[] = $this->greenTick.' Boost';
                } else {
                    $results[] = $this->redCross.' Boost';
                    $failed[$agentName]['boost'] = 'Failed to write configuration';
                }
            } catch (Exception $e) {
                $results[] = $this->redCross.' Boost';
                $failed[$agentName]['boost'] = $e->getMessage();
            }

            // Install Herd MCP if enabled
            if ($this->shouldInstallHerdMcp()) {
                $php = $agent->getPhpPath();

                try {
                    $result = $agent->installMcp(
                        key: 'herd',
                        command: $php,
                        args: [$this->herd->mcpPath()],
                        env: ['SITE_PATH' => base_path()]
                    );

                    if ($result) {
                        $results[] = $this->greenTick.' Herd';
                    } else {
                        $results[] = $this->redCross.' Herd';
                        $failed[$agentName]['herd'] = 'Failed to write configuration';
                    }
                } catch (Exception $e) {
                    $results[] = $this->redCross.' Herd';
                    $failed[$agentName]['herd'] = $e->getMessage();
                }
            }

            $this->line(implode(' ', $results));
        }

        $this->newLine();

        if ($failed !== []) {
            $this->error(sprintf('%s Some MCP servers failed to install:', $this->redCross));

            foreach ($failed as $agentName => $errors) {
                foreach ($errors as $server => $error) {
                    $this->line("  - {$agentName} ({$server}): {$error}");
                }
            }
        }
    }

    /**
     * @template T
     *
     * @param  Collection<int, T>  $items
     * @param  callable(T): string  $nameResolver
     * @param  callable(T): mixed  $processor
     */
    protected function processWithProgress(
        Collection $items,
        callable $nameResolver,
        callable $processor,
        string $entityName,
    ): void {
        $failed = [];

        $longestName = max(1, ...$items->map(fn ($item) => Str::length($nameResolver($item)))->toArray());

        foreach ($items as $item) {
            $name = $nameResolver($item);
            $displayName = str_pad($name, $longestName);
            $this->output->write("  {$displayName}... ");

            try {
                $processor($item);
                $this->line($this->greenTick);
            } catch (Exception $e) {
                $failed[$name] = $e->getMessage();
                $this->line($this->redCross);
            }
        }

        if ($failed !== []) {
            $this->newLine();
            $this->error(sprintf('✗ Failed to install %s to %d agent%s:',
                $entityName,
                count($failed),
                count($failed) === 1 ? '' : 's'
            ));
            foreach ($failed as $agentName => $error) {
                $this->line("  - {$agentName}: {$error}");
            }
        }
    }

    /**
     * Is the project actually using localization for their new features?
     */
    protected function detectLocalization(): bool
    {
        $actuallyUsing = false;

        /** @phpstan-ignore-next-line  */
        return $actuallyUsing && is_dir(base_path('lang'));
    }

    /**
     * Are we running inside a Windows Subsystem for Linux (WSL) environment?
     * This differentiates between a regular Linux installation and a WSL.
     */
    private function isRunningInWsl(): bool
    {
        // Check for WSL-specific environment variables.
        return ! empty(getenv('WSL_DISTRO_NAME')) || ! empty(getenv('IS_WSL'));
    }
}
