<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Cli\DisplayHelper;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\CodeEnvironmentsDetector;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\GuidelineWriter;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Terminal;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;

class InstallCommand extends Command
{
    use Colors;

    protected $signature = 'boost:install {--ignore-guidelines : Skip installing AI guidelines} {--ignore-mcp : Skip installing MCP server configuration}';

    private CodeEnvironmentsDetector $codeEnvironmentsDetector;

    private Herd $herd;

    private Sail $sail;

    private Terminal $terminal;

    /** @var Collection<int, Agent> */
    private Collection $selectedTargetAgents;

    /** @var Collection<int, McpClient> */
    private Collection $selectedTargetMcpClient;

    /** @var Collection<int, string> */
    private Collection $selectedBoostFeatures;

    /** @var Collection<int, string> */
    private Collection $selectedAiGuidelines;

    private string $projectName;

    /** @var array<non-empty-string> */
    private array $systemInstalledCodeEnvironments = [];

    private array $projectInstalledCodeEnvironments = [];

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
        CodeEnvironmentsDetector $codeEnvironmentsDetector,
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

        $this->bootstrap($codeEnvironmentsDetector, $herd, $sail, $terminal);
        $this->displayBoostHeader();
        $this->discoverEnvironment();
        $this->collectInstallationPreferences();
        $this->performInstallation();
        $this->outro();

        return self::SUCCESS;
    }

    protected function bootstrap(CodeEnvironmentsDetector $codeEnvironmentsDetector, Herd $herd, Sail $sail, Terminal $terminal): void
    {
        $this->codeEnvironmentsDetector = $codeEnvironmentsDetector;
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
        $this->systemInstalledCodeEnvironments = $this->codeEnvironmentsDetector->discoverSystemInstalledCodeEnvironments();
        $this->projectInstalledCodeEnvironments = $this->codeEnvironmentsDetector->discoverProjectInstalledCodeEnvironments(base_path());
    }

    protected function collectInstallationPreferences(): void
    {
        $this->selectedBoostFeatures = $this->selectBoostFeatures();
        $this->selectedAiGuidelines = $this->selectAiGuidelines();
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

        $ideNames = $this->selectedTargetMcpClient->map(fn (McpClient $mcpClient): string => 'i:'.$mcpClient->mcpClientName())
            ->toArray();
        $agentNames = $this->selectedTargetAgents->map(fn (Agent $agent): string => 'a:'.$agent->agentName())->toArray();
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
    protected function selectAiGuidelines(): Collection
    {
        if (! $this->installGuidelines) {
            return collect();
        }

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
     * @return Collection<int, CodeEnvironment>
     */
    protected function selectTargetMcpClients(): Collection
    {
        if (! $this->installMcpConfig) {
            return collect();
        }

        return $this->selectCodeEnvironments(
            McpClient::class,
            sprintf('Which code editors do you use to work on %s?', $this->projectName),
            $this->config->getEditors(),
        );
    }

    /**
     * @return Collection<int, CodeEnvironment>
     */
    protected function selectTargetAgents(): Collection
    {
        if (! $this->installGuidelines) {
            return collect();
        }

        $defaults = $this->config->getAgents();

        if ($this->selectedTargetMcpClient->isNotEmpty()) {
            $defaults = $this->selectedTargetMcpClient
                ->filter(fn (McpClient $client): bool => $client instanceof Agent)
                ->map(fn (McpClient $client): string => $client->name())
                ->values()
                ->toArray();
        }

        return $this->selectCodeEnvironments(
            Agent::class,
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
            Agent::class => ['required' => false, 'displayMethod' => 'agentName'],
            McpClient::class => ['required' => true, 'displayMethod' => 'displayName'],
            default => throw new InvalidArgumentException("Unsupported contract class: {$contractClass}"),
        };
    }

    /**
     * @param  array<int, string>  $defaults
     * @return Collection<int, CodeEnvironment>
     */
    protected function selectCodeEnvironments(string $contractClass, string $label, array $defaults): Collection
    {
        $allEnvironments = $this->codeEnvironmentsDetector->getCodeEnvironments();
        $config = $this->getSelectionConfig($contractClass);

        $availableEnvironments = $allEnvironments->filter(fn (CodeEnvironment $environment): bool => $environment instanceof $contractClass);

        if ($availableEnvironments->isEmpty()) {
            return collect();
        }

        $options = $availableEnvironments->mapWithKeys(function (CodeEnvironment $environment) use ($config): array {
            $displayMethod = $config['displayMethod'];
            $displayText = $environment->{$displayMethod}();

            return [$environment->name() => $displayText];
        })->sort();

        $installedEnvNames = array_unique(array_merge(
            $this->projectInstalledCodeEnvironments,
            $this->systemInstalledCodeEnvironments
        ));

        $detectedDefaults = [];

        if ($defaults === []) {
            foreach ($installedEnvNames as $envKey) {
                $matchingEnv = $availableEnvironments->first(fn (CodeEnvironment $env): bool => strtolower((string) $envKey) === strtolower($env->name()));
                if ($matchingEnv) {
                    $detectedDefaults[] = $matchingEnv->name();
                }
            }
        }

        $selectedCodeEnvironments = collect(multiselect(
            label: $label,
            options: $options->toArray(),
            default: $defaults === [] ? $detectedDefaults : $defaults,
            scroll: $options->count(),
            required: $config['required'],
            hint: $defaults === [] || $detectedDefaults === [] ? '' : sprintf('Auto-detected %s for you',
                Arr::join(array_map(function ($className) use ($availableEnvironments, $config) {
                    $env = $availableEnvironments->first(fn ($env): bool => $env->name() === $className);
                    $displayMethod = $config['displayMethod'];

                    return $env->{$displayMethod}();
                }, $detectedDefaults), ', ', ' & ')
            )
        ))->sort();

        return $selectedCodeEnvironments->map(
            fn (string $name) => $availableEnvironments->first(fn ($env): bool => $env->name() === $name),
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

        $longestAgentName = max(1, ...$this->selectedTargetAgents->map(fn ($agent) => Str::length($agent->agentName()))->toArray());
        /** @var CodeEnvironment $agent */
        foreach ($this->selectedTargetAgents as $agent) {
            $agentName = $agent->agentName();
            $displayAgentName = str_pad((string) $agentName, $longestAgentName);
            $this->output->write("  {$displayAgentName}... ");
            /** @var Agent $agent */
            try {
                (new GuidelineWriter($agent))
                    ->write($composedAiGuidelines);

                $this->line($this->greenTick);
            } catch (Exception $e) {
                $failed[$agentName] = $e->getMessage();
                $this->line($this->redCross);
            }
        }

        $this->newLine();

        if ($failed !== []) {
            $this->error(sprintf('✗ Failed to install guidelines to %d agent%s:',
                count($failed),
                count($failed) === 1 ? '' : 's'
            ));
            foreach ($failed as $agentName => $error) {
                $this->line("  - {$agentName}: {$error}");
            }
        }

        if ($this->installMcpConfig) {
            $this->config->setSail(
                $this->shouldUseSail()
            );

            $this->config->setHerdMcp(
                $this->shouldInstallHerdMcp()
            );

            $this->config->setEditors(
                $this->selectedTargetMcpClient->map(fn (McpClient $mcpClient): string => $mcpClient->name())->values()->toArray()
            );
        }

        $this->config->setAgents(
            $this->selectedTargetAgents->map(fn (Agent $agent): string => $agent->name())->values()->toArray()
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

    protected function shouldUseSail(): bool
    {
        if ($this->selectedBoostFeatures->isEmpty()) {
            return $this->config->getSail();
        }

        return $this->selectedBoostFeatures->contains('sail');
    }

    protected function buildMcpCommand(McpClient $mcpClient): array
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
                fn (McpClient $mcpClient) => Str::length($mcpClient->mcpClientName())
            )->toArray()
        );

        foreach ($this->selectedTargetMcpClient as $mcpClient) {
            $ideName = $mcpClient->mcpClientName();
            $ideDisplay = str_pad((string) $ideName, $longestIdeName);
            $this->output->write("  {$ideDisplay}... ");
            $results = [];

            $mcp = $this->buildMcpCommand($mcpClient);

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
