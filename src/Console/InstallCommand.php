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
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;

#[AsCommand('boost:install', 'Install Laravel Boost')]
class InstallCommand extends Command
{
    use Colors;

    private CodeEnvironmentsDetector $codeEnvironmentsDetector;

    private Herd $herd;

    private Terminal $terminal;

    /** @var Collection<int, Agent> */
    private Collection $selectedTargetAgents;

    /** @var Collection<int, McpClient> */
    private Collection $selectedTargetMcpClient;

    /** @var Collection<int, string> */
    private Collection $selectedBoostFeatures;

    private string $projectName;

    /** @var array<non-empty-string> */
    private array $systemInstalledCodeEnvironments = [];

    private array $projectInstalledCodeEnvironments = [];

    private bool $enforceTests = true;

    const MIN_TEST_COUNT = 6;

    private string $greenTick;

    private string $redCross;

    public function handle(CodeEnvironmentsDetector $codeEnvironmentsDetector, Herd $herd, Terminal $terminal): void
    {
        $this->bootstrap($codeEnvironmentsDetector, $herd, $terminal);

        $this->displayBoostHeader();
        $this->discoverEnvironment();
        $this->collectInstallationPreferences();
        $this->performInstallation();
        $this->outro();
    }

    private function bootstrap(CodeEnvironmentsDetector $codeEnvironmentsDetector, Herd $herd, Terminal $terminal): void
    {
        $this->codeEnvironmentsDetector = $codeEnvironmentsDetector;
        $this->herd = $herd;
        $this->terminal = $terminal;

        $this->terminal->initDimensions();
        $this->greenTick = $this->green('✓');
        $this->redCross = $this->red('✗');

        $this->selectedTargetAgents = collect();
        $this->selectedTargetMcpClient = collect();

        $this->projectName = config('app.name');
    }

    private function displayBoostHeader(): void
    {
        note($this->boostLogo());
        intro('✦ Laravel Boost :: Install :: We Must Ship ✦');
        note("Let's give {$this->bgYellow($this->black($this->bold($this->projectName)))} a Boost");
    }

    private function boostLogo(): string
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

    private function discoverEnvironment(): void
    {
        $this->systemInstalledCodeEnvironments = $this->codeEnvironmentsDetector->discoverSystemInstalledCodeEnvironments();
        $this->projectInstalledCodeEnvironments = $this->codeEnvironmentsDetector->discoverProjectInstalledCodeEnvironments(base_path());
    }

    private function collectInstallationPreferences(): void
    {
        $this->selectedBoostFeatures = $this->selectBoostFeatures();
        $this->selectedTargetMcpClient = $this->selectTargetMcpClients();
        $this->selectedTargetAgents = $this->selectTargetAgents();
        $this->enforceTests = $this->determineTestEnforcement(ask: false);
    }

    private function performInstallation(): void
    {
        $this->installGuidelines();

        usleep(750000);

        if (($this->shouldInstallMcp() || $this->shouldInstallHerdMcp()) && $this->selectedTargetMcpClient->isNotEmpty()) {
            $this->installMcpServerConfig();
        }
    }

    private function discoverTools(): array
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

    private function outro(): void
    {
        $label = 'https://boost.laravel.com/installed';

        $ideNames = $this->selectedTargetMcpClient->map(fn (McpClient $mcpClient) => 'i:'.$mcpClient->mcpClientName())
            ->toArray();
        $agentNames = $this->selectedTargetAgents->map(fn (Agent $agent) => 'a:'.$agent->agentName())->toArray();
        $boostFeatures = $this->selectedBoostFeatures->map(fn ($feature) => 'b:'.$feature)->toArray();

        $guidelines = [];
        if ($this->shouldInstallAiGuidelines()) {
            $guidelines[] = 'g:ai';
        }

        if ($this->shouldInstallStyleGuidelines()) {
            $guidelines[] = 'g:style';
        }

        $allData = array_merge($ideNames, $agentNames, $boostFeatures, $guidelines);
        $installData = base64_encode(implode(',', $allData));

        $link = $this->hyperlink($label, 'https://boost.laravel.com/installed/?d='.$installData);

        $text = 'Enjoy the boost 🚀 Next steps: ';
        $paddingLength = (int) (floor(($this->terminal->cols() - mb_strlen($text.$label)) / 2)) - 2;

        echo "\033[42m\033[2K".str_repeat(' ', max(0, $paddingLength)); // Make the entire line have a green background
        echo $this->black($this->bold($text.$link)).$this->reset(PHP_EOL).$this->reset(PHP_EOL);
    }

    private function hyperlink(string $label, string $url): string
    {
        return "\033]8;;{$url}\007{$label}\033]8;;\033\\";
    }

    /**
     * We shouldn't add an AI guideline enforcing tests if they don't have a basic test setup.
     * This would likely just create headaches for them or be a waste of time as they
     * won't have the CI setup to make use of them anyway, so we're just wasting their
     * tokens/money by enforcing them.
     */
    protected function determineTestEnforcement(bool $ask = true): bool
    {
        $hasMinimumTests = false;

        if (file_exists(base_path('vendor/bin/phpunit'))) {
            $process = new Process([PHP_BINARY, 'artisan', 'test', '--list-tests'], base_path());
            $process->run();

            /** Count the number of tests - they'll always have :: between the filename and test name */
            $hasMinimumTests = Str::of($process->getOutput())
                ->trim()
                ->explode("\n")
                ->filter(fn ($line) => str_contains($line, '::'))
                ->count() >= self::MIN_TEST_COUNT;
        }

        if (! $hasMinimumTests && $ask) {
            $hasMinimumTests = select(
                label: 'Should AI always create tests?',
                options: ['Yes', 'No'],
                default: 'Yes'
            ) === 'Yes';
        }

        return $hasMinimumTests;
    }

    /**
     * @return Collection<int, string>
     */
    private function selectBoostFeatures(): Collection
    {
        $defaultInstallOptions = ['mcp_server', 'ai_guidelines'];
        $installOptions = [
            'mcp_server' => 'Boost MCP Server (with 15+ tools)',
            'ai_guidelines' => 'Boost AI Guidelines (for Laravel, Inertia, and more)',
        ];

        if ($this->herd->isMcpAvailable()) {
            $installOptions['herd_mcp'] = 'Herd MCP Server';

            return collect(multiselect(
                label: 'What do you want to install?',
                options: $installOptions,
                default: $defaultInstallOptions,
                required: true,
            ));
        }

        return collect(['mcp_server', 'ai_guidelines']);
    }

    /**
     * @return array<int, string>
     */
    protected function boostToolsToDisable(): array
    {
        return multiselect(
            label: 'Do you need to disable any Boost provided tools?',
            options: $this->discoverTools(),
            scroll: 4,
            hint: 'You can exclude or include them later in the config file',
        );
    }

    /**
     * @return array<int, string>
     */

    /**
     * @return Collection<int, CodeEnvironment>
     */
    private function selectTargetMcpClients(): Collection
    {
        if (! $this->shouldInstallMcp() && ! $this->shouldInstallHerdMcp()) {
            return collect();
        }

        return $this->selectCodeEnvironments(
            McpClient::class,
            sprintf('Which code editors do you use to work on %s?', $this->projectName)
        );
    }

    /**
     * @return Collection<int, CodeEnvironment>
     */
    private function selectTargetAgents(): Collection
    {
        if (! $this->shouldInstallAiGuidelines()) {
            return collect();
        }

        return $this->selectCodeEnvironments(
            Agent::class,
            sprintf('Which agents need AI guidelines for %s?', $this->projectName)
        );
    }

    /**
     * Get configuration settings for contract-specific selection behavior.
     *
     * @return array{scroll: int, required: bool, displayMethod: string}
     */
    private function getSelectionConfig(string $contractClass): array
    {
        return match ($contractClass) {
            Agent::class => ['scroll' => 4, 'required' => false, 'displayMethod' => 'agentName'],
            McpClient::class => ['scroll' => 5, 'required' => true, 'displayMethod' => 'displayName'],
            default => throw new InvalidArgumentException("Unsupported contract class: {$contractClass}"),
        };
    }

    /**
     * @return Collection<int, CodeEnvironment>
     */
    private function selectCodeEnvironments(string $contractClass, string $label): Collection
    {
        $allEnvironments = $this->codeEnvironmentsDetector->getCodeEnvironments();
        $config = $this->getSelectionConfig($contractClass);

        $availableEnvironments = $allEnvironments->filter(function (CodeEnvironment $environment) use ($contractClass) {
            return $environment instanceof $contractClass;
        });

        if ($availableEnvironments->isEmpty()) {
            return collect();
        }

        $options = $availableEnvironments->mapWithKeys(function (CodeEnvironment $environment) use ($config) {
            $displayMethod = $config['displayMethod'];
            $displayText = $environment->{$displayMethod}();

            return [get_class($environment) => $displayText];
        })->sort();

        $detectedClasses = [];
        $installedEnvNames = array_unique(array_merge(
            $this->projectInstalledCodeEnvironments,
            $this->systemInstalledCodeEnvironments
        ));

        foreach ($installedEnvNames as $envKey) {
            $matchingEnv = $availableEnvironments->first(fn (CodeEnvironment $env) => strtolower($envKey) === strtolower($env->name()));
            if ($matchingEnv) {
                $detectedClasses[] = get_class($matchingEnv);
            }
        }

        $selectedClasses = collect(multiselect(
            label: $label,
            options: $options->toArray(),
            default: array_unique($detectedClasses),
            scroll: $config['scroll'],
            required: $config['required'],
            hint: empty($detectedClasses) ? '' : sprintf('Auto-detected %s for you',
                Arr::join(array_map(function ($className) use ($availableEnvironments, $config) {
                    $env = $availableEnvironments->first(fn ($env) => get_class($env) === $className);
                    $displayMethod = $config['displayMethod'];

                    return $env->{$displayMethod}();
                }, $detectedClasses), ', ', ' & ')
            )
        ))->sort();

        return $selectedClasses->map(fn ($className) => $availableEnvironments->first(fn ($env) => get_class($env) === $className));
    }

    private function installGuidelines(): void
    {
        if (! $this->shouldInstallAiGuidelines()) {
            return;
        }

        if ($this->selectedTargetAgents->isEmpty()) {
            $this->info(' No agents selected for guideline installation.');

            return;
        }

        $guidelineConfig = new GuidelineConfig;
        $guidelineConfig->enforceTests = $this->enforceTests;
        $guidelineConfig->laravelStyle = $this->shouldInstallStyleGuidelines();
        $guidelineConfig->caresAboutLocalization = $this->detectLocalization();
        $guidelineConfig->hasAnApi = false;

        $composer = app(GuidelineComposer::class)->config($guidelineConfig);
        $guidelines = $composer->guidelines();

        $this->newLine();
        $this->info(sprintf(' Adding %d guidelines to your selected agents', $guidelines->count()));
        DisplayHelper::grid($guidelines->keys()->sort()->toArray(), $this->terminal->cols());
        $this->newLine();
        usleep(750000);

        $failed = [];
        $composedAiGuidelines = $composer->compose();

        $longestAgentName = max(1, ...$this->selectedTargetAgents->map(fn ($agent) => Str::length($agent->agentName()))->toArray());
        /** @var CodeEnvironment $agent */
        foreach ($this->selectedTargetAgents as $agent) {
            $agentName = $agent->agentName();
            $displayAgentName = str_pad($agentName, $longestAgentName);
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

        if (count($failed) > 0) {
            $this->error(sprintf('✗ Failed to install guidelines to %d agent%s:',
                count($failed),
                count($failed) === 1 ? '' : 's'
            ));
            foreach ($failed as $agentName => $error) {
                $this->line("  - {$agentName}: {$error}");
            }
        }
    }

    private function shouldInstallAiGuidelines(): bool
    {
        return $this->selectedBoostFeatures->contains('ai_guidelines');
    }

    private function shouldInstallStyleGuidelines(): bool
    {
        return false;
    }

    private function shouldInstallMcp(): bool
    {
        return $this->selectedBoostFeatures->contains('mcp_server');
    }

    private function shouldInstallHerdMcp(): bool
    {
        return $this->selectedBoostFeatures->contains('herd_mcp');
    }

    private function installMcpServerConfig(): void
    {
        if (! $this->shouldInstallMcp() && ! $this->shouldInstallHerdMcp()) {
            return;
        }

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

        /** @var McpClient $mcpClient */
        foreach ($this->selectedTargetMcpClient as $mcpClient) {
            $ideName = $mcpClient->mcpClientName();
            $ideDisplay = str_pad($ideName, $longestIdeName);
            $this->output->write("  {$ideDisplay}... ");
            $results = [];

            $php = $mcpClient->getPhpPath();
            if ($this->shouldInstallMcp()) {
                try {
                    $artisan = $mcpClient->getArtisanPath();
                    $result = $mcpClient->installMcp('laravel-boost', $php, [$artisan, 'boost:mcp']);

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
            }

            // Install Herd MCP if enabled
            if ($this->shouldInstallHerdMcp()) {
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

        if (count($failed) > 0) {
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
    private function detectLocalization(): bool
    {
        $actuallyUsing = false;

        /** @phpstan-ignore-next-line  */
        return $actuallyUsing && is_dir(base_path('lang'));
    }
}
