<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Boost\Contracts\Guideline;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Cli\DisplayHelper;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\GuidelineWriter;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;

#[AsCommand('boost:install', 'Install Laravel Boost')]
class InstallCommand extends Command
{
    use Colors;

    private AgentsDetector $agentsDetector;

    private Herd $herd;

    private Terminal $terminal;

    /** @var Collection<int, Guideline> */
    private Collection $selectedGuidelineProviders;

    /** @var Collection<int, McpClient> */
    private Collection $selectedTargetMcpClientProviders;

    /** @var Collection<int, string> */
    private Collection $selectedBoostFeatures;

    /** @var Collection<int, string> */
    private Collection $selectedAiGuidelines;

    private string $projectName;

    /** @var array<class-string<Agent>> */
    private array $systemInstalledAgents = [];

    /** @var array<class-string<Agent>> */
    private array $projectInstalledAgent = [];

    private bool $enforceTests = true;

    const MIN_TEST_COUNT = 6;

    private string $greenTick;

    private string $redCross;

    public function __construct(protected Config $config)
    {
        parent::__construct();
    }

    public function handle(AgentsDetector $agentsDetector, Herd $herd, Terminal $terminal): void
    {
        $this->bootstrap($agentsDetector, $herd, $terminal);

        $this->displayBoostHeader();
        $this->discoverAgents();
        $this->collectInstallationPreferences();
        $this->performInstallation();
        $this->outro();
    }

    protected function bootstrap(AgentsDetector $agentsDetector, Herd $herd, Terminal $terminal): void
    {
        $this->agentsDetector = $agentsDetector;
        $this->herd = $herd;
        $this->terminal = $terminal;

        $this->terminal->initDimensions();

        $this->greenTick = $this->green('✓');
        $this->redCross = $this->red('✗');

        $this->selectedGuidelineProviders = collect();
        $this->selectedTargetMcpClientProviders = collect();

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

    protected function discoverAgents(): void
    {
        $this->systemInstalledAgents = $this->agentsDetector->discoverSystemInstalledAgents();
        $this->projectInstalledAgent = $this->agentsDetector->discoverProjectInstalledAgents(base_path());
    }

    protected function collectInstallationPreferences(): void
    {
        $this->selectedBoostFeatures = $this->selectBoostFeatures();
        $this->selectedAiGuidelines = $this->selectAiGuidelines();
        $this->selectedTargetMcpClientProviders = $this->selectTargetMcpClientProvider();
        $this->selectedGuidelineProviders = $this->selectTargetAgents();
        $this->enforceTests = $this->determineTestEnforcement();
    }

    protected function performInstallation(): void
    {
        $this->installGuidelines();

        usleep(750000);

        if ($this->selectedTargetMcpClientProviders->isNotEmpty()) {
            $this->installMcpServerConfig();
        }
    }

    protected function discoverTools(): array
    {
        $tools = [];
        $toolDir = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Mcp', 'Tools']);
        $finder = Finder::create()
            ->in($toolDir)
            ->files()
            ->name('*.php');

        foreach ($finder as $toolFile) {
            $fullyClassifiedClassName = 'Laravel\\Boost\\Mcp\\Tools\\'.$toolFile->getBasename('.php');
            if (class_exists($fullyClassifiedClassName, false)) {
                $tools[$fullyClassifiedClassName] = Str::headline($toolFile->getBasename('.php'));
            }
        }

        ksort($tools);

        return $tools;
    }

    protected function outro(): void
    {
        $label = 'https://boost.laravel.com/installed';

        $ideNames = $this->selectedTargetMcpClientProviders->map(fn (McpClient $mcpClient): string => 'i:'.$mcpClient->mcpClientName())
            ->toArray();
        $agentNames = $this->selectedGuidelineProviders->map(fn (Guideline $guideline): string => 'a:'.$guideline->guidelineProviderName())->toArray();
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
        $features = collect([
            'mcp_server',
            'ai_guidelines',
        ]);

        if ($this->herd->isMcpAvailable() === false) {
            return $features;
        }

        if (confirm(
            label: 'Would you like to install Herd MCP alongside Boost MCP?',
            default: $this->config->getHerdMcp(),
            hint: 'The Herd MCP provides additional tools like browser logs, which can help AI understand issues better',
        )) {
            $features->push('herd_mcp');
        }

        return $features;
    }

    /**
     * @return Collection<int, string>
     */
    protected function selectAiGuidelines(): Collection
    {
        $options = app(GuidelineComposer::class)->guidelines()
            ->reject(fn (array $guideline): bool => $guideline['third_party'] === false);

        if ($options->isEmpty()) {
            return collect();
        }

        return collect(multiselect(
            label: 'Which third-party AI guidelines do you want to install?',
            // @phpstan-ignore-next-line
            options: $options->mapWithKeys(function (array $guideline, string $name): array {
                $humanName = str_replace('/core', '', $name);

                return [$name => "{$humanName} (~{$guideline['tokens']} tokens) {$guideline['description']}"];
            }),
            default: collect($this->config->getGuidelines()),
            scroll: 10,
            hint: 'You can add or remove them later by running this command again',
        ));
    }

    /**
     * @return Collection<int, McpClient>
     */
    protected function selectTargetMcpClientProvider(): Collection
    {
        return $this->selectAgents(
            McpClient::class,
            sprintf('Which code editors do you use to work on %s?', $this->projectName),
            $this->config->getEditors(),
        );
    }

    /**
     * @return Collection<int, Guideline>
     */
    protected function selectTargetAgents(): Collection
    {
        return $this->selectAgents(
            Guideline::class,
            sprintf('Which agents need AI guidelines for %s?', $this->projectName),
            $this->config->getAgents(),
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
            Guideline::class => ['required' => false, 'displayMethod' => 'guidelineProviderName'],
            McpClient::class => ['required' => true, 'displayMethod' => 'mcpClientName'],
            default => throw new InvalidArgumentException("Unsupported contract class: {$contractClass}"),
        };
    }

    /**
     * @param  array<int, string>  $defaults
     * @return Collection<int, Agent>
     */
    protected function selectAgents(string $contractClass, string $label, array $defaults): Collection
    {
        $agents = $this->agentsDetector->getAgents();
        $config = $this->getSelectionConfig($contractClass);

        $availableAgents = $agents->filter(fn (Agent $agent): bool => $agent instanceof $contractClass);

        if ($availableAgents->isEmpty()) {
            return collect();
        }

        $options = $availableAgents->mapWithKeys(function (Agent $agent) use ($config): array {
            $displayMethod = $config['displayMethod'];
            $displayText = $agent->{$displayMethod}();

            return [$agent->name() => $displayText];
        })->sort();

        $installedEnvNames = array_unique(array_merge(
            $this->projectInstalledAgent,
            $this->systemInstalledAgents
        ));

        $detectedDefaults = [];

        if ($defaults === []) {
            foreach ($installedEnvNames as $envKey) {
                $matchingEnv = $availableAgents->first(fn (Agent $env): bool => strtolower((string) $envKey) === strtolower($env->name()));
                if ($matchingEnv) {
                    $detectedDefaults[] = $matchingEnv->name();
                }
            }
        }

        $selectedAgent = collect(multiselect(
            label: $label,
            options: $options->toArray(),
            default: $defaults === [] ? $detectedDefaults : $defaults,
            scroll: min(10, $options->count()),
            required: $config['required'],
            hint: $defaults === [] || $detectedDefaults === [] ? '' : sprintf('Auto-detected %s for you',
                Arr::join(array_map(function ($className) use ($availableAgents, $config) {
                    $env = $availableAgents->first(fn ($env): bool => $env->name() === $className);
                    $displayMethod = $config['displayMethod'];

                    return $env->{$displayMethod}();
                }, $detectedDefaults), ', ', ' & ')
            )
        ))->sort();

        return $selectedAgent->map(
            fn (string $name) => $availableAgents->first(fn ($env): bool => $env->name() === $name),
        )->filter()->values();
    }

    protected function installGuidelines(): void
    {
        if ($this->selectedGuidelineProviders->isEmpty()) {
            $this->info(' No agents are selected for guideline installation.');

            return;
        }

        $guidelineConfig = new GuidelineConfig;
        $guidelineConfig->enforceTests = $this->enforceTests;
        $guidelineConfig->laravelStyle = $this->shouldInstallStyleGuidelines();
        $guidelineConfig->caresAboutLocalization = $this->detectLocalization();
        $guidelineConfig->hasAnApi = false;
        $guidelineConfig->aiGuidelines = $this->selectedAiGuidelines->values()->toArray();

        $composer = app(GuidelineComposer::class)->config($guidelineConfig);
        $guidelines = $composer->guidelines();

        $this->newLine();
        $this->info(sprintf(' Adding %d guidelines to your selected agents', $guidelines->count()));
        DisplayHelper::grid(
            $guidelines
                ->map(fn ($guideline, string $key): string => $key.($guideline['custom'] ? '*' : ''))
                ->values()
                ->sort()
                ->toArray(),
            $this->terminal->cols()
        );
        $this->newLine();
        usleep(750000);

        $failed = [];
        $composedAiGuidelines = $composer->compose();

        $longestAgentName = max(1, ...$this->selectedGuidelineProviders->map(fn (Guideline $guideline) => Str::length($guideline->guidelineProviderName()))->toArray());
        foreach ($this->selectedGuidelineProviders as $guideline) {
            $agentName = $guideline->guidelineProviderName();
            $displayAgentName = str_pad((string) $agentName, $longestAgentName);
            $this->output->write("  {$displayAgentName}... ");
            /** @var Guideline $guideline */
            try {
                (new GuidelineWriter($guideline))
                    ->write($composedAiGuidelines);

                $this->line($this->greenTick);
            } catch (Exception $e) {
                $failed[$agentName] = $e->getMessage();
                $this->line($this->redCross);
            }
        }

        $this->newLine();

        if ($failed !== []) {
            $this->error(sprintf('✗ Failed to install guidelines to %d guideline %s:',
                count($failed),
                count($failed) === 1 ? '' : 's'
            ));
            foreach ($failed as $agentName => $error) {
                $this->line("  - {$agentName}: {$error}");
            }
        }

        $this->config->setHerdMcp(
            $this->shouldInstallHerdMcp()
        );

        $this->config->setEditors(
            $this->selectedTargetMcpClientProviders->map(fn (McpClient $mcpClient): string => $mcpClient->name())->values()->toArray()
        );

        $this->config->setAgents(
            $this->selectedGuidelineProviders->map(fn (Guideline $guideline): string => $guideline->name())->values()->toArray()
        );

        $this->config->setGuidelines(
            $this->selectedAiGuidelines->values()->toArray()
        );
    }

    protected function shouldInstallStyleGuidelines(): bool
    {
        return false;
    }

    protected function shouldInstallHerdMcp(): bool
    {
        return $this->selectedBoostFeatures->contains('herd_mcp');
    }

    protected function installMcpServerConfig(): void
    {
        if ($this->selectedTargetMcpClientProviders->isEmpty()) {
            $this->info('No agents are selected for guideline installation.');

            return;
        }

        $this->newLine();
        $this->info(' Installing MCP servers to your selected IDEs');
        $this->newLine();

        usleep(750000);

        $failed = [];
        $longestIdeName = max(
            1,
            ...$this->selectedTargetMcpClientProviders->map(
                fn (McpClient $mcpClient) => Str::length($mcpClient->mcpClientName())
            )->toArray()
        );

        /** @var McpClient $mcpClient */
        foreach ($this->selectedTargetMcpClientProviders as $mcpClient) {
            $ideName = $mcpClient->mcpClientName();
            $ideDisplay = str_pad((string) $ideName, $longestIdeName);
            $this->output->write("  {$ideDisplay}... ");
            $results = [];

            $inWsl = $this->isRunningInWsl();
            $mcp = array_filter([
                'laravel-boost',
                $inWsl ? 'wsl' : false,
                $mcpClient->getPhpPath($inWsl),
                $mcpClient->getArtisanPath($inWsl),
                'boost:mcp',
            ]);
            try {
                $result = $mcpClient->installMcp(
                    array_shift($mcp),
                    array_shift($mcp),
                    $mcp
                );

                if ($result) {
                    $results[] = $this->greenTick.' Boost';
                } else {
                    $results[] = $this->redCross.' Boost';
                    $failed[$ideName]['boost'] = 'Failed to write configuration';
                }
            } catch (Exception $e) {
                $results[] = $this->redCross.' Boost';
                $failed[$ideName]['boost'] = $e->getMessage();
            }

            // Install Herd MCP if enabled
            if ($this->shouldInstallHerdMcp()) {
                $php = $mcpClient->getPhpPath();
                try {
                    $result = $mcpClient->installMcp(
                        key: 'herd',
                        command: $php,
                        args: [$this->herd->mcpPath()],
                        env: ['SITE_PATH' => base_path()]
                    );

                    if ($result) {
                        $results[] = $this->greenTick.' Herd';
                    } else {
                        $results[] = $this->redCross.' Herd';
                        $failed[$ideName]['herd'] = 'Failed to write configuration';
                    }
                } catch (Exception $e) {
                    $results[] = $this->redCross.' Herd';
                    $failed[$ideName]['herd'] = $e->getMessage();
                }
            }

            $this->line(implode(' ', $results));
        }

        $this->newLine();

        if ($failed !== []) {
            $this->error(sprintf('%s Some MCP servers failed to install:', $this->redCross));
            foreach ($failed as $ideName => $errors) {
                foreach ($errors as $server => $error) {
                    $this->line("  - {$ideName} ({$server}): {$error}");
                }
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
