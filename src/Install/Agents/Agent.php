<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Illuminate\Support\Facades\Process;
use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Laravel\Boost\Install\Mcp\FileWriter;
use Laravel\Boost\Install\Mcp\TomlFileWriter;

abstract class Agent
{
    public function __construct(protected readonly DetectionStrategyFactory $strategyFactory)
    {
        //
    }

    abstract public function name(): string;

    abstract public function displayName(): string;

    public function useAbsolutePathForMcp(): bool
    {
        return false;
    }

    public function getPhpPath(bool $forceAbsolutePath = false): string
    {
        $phpBinaryPath = config('boost.executable_paths.php') ?? 'php';

        if ($phpBinaryPath === 'php' && ($this->useAbsolutePathForMcp() || $forceAbsolutePath)) {
            return PHP_BINARY;
        }

        return $phpBinaryPath;
    }

    public function getArtisanPath(bool $forceAbsolutePath = false): string
    {
        return ($this->useAbsolutePathForMcp() || $forceAbsolutePath) ? base_path('artisan') : 'artisan';
    }

    /**
     * Get the detection configuration for system-wide installation detection.
     *
     * @return array{paths?: string[], command?: string, files?: string[]}
     */
    abstract public function systemDetectionConfig(Platform $platform): array;

    /**
     * Get the detection configuration for project-specific detection.
     *
     * @return array{paths?: string[], files?: string[]}
     */
    abstract public function projectDetectionConfig(): array;

    public function detectOnSystem(Platform $platform): bool
    {
        $config = $this->systemDetectionConfig($platform);
        $strategy = $this->strategyFactory->makeFromConfig($config);

        return $strategy->detect($config, $platform);
    }

    public function detectInProject(string $basePath): bool
    {
        $config = array_merge($this->projectDetectionConfig(), ['basePath' => $basePath]);
        $strategy = $this->strategyFactory->makeFromConfig($config);

        return $strategy->detect($config);
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    public static function fromName(string $name): ?Agent
    {
        $detectionFactory = app(DetectionStrategyFactory::class);
        $boostManager = app(BoostManager::class);

        foreach ($boostManager->getAgents() as $class) {
            /** @var class-string<Agent> $class */
            $instance = new $class($detectionFactory);

            if ($instance->name() === $name) {
                return $instance;
            }
        }

        return null;
    }

    public function shellMcpCommand(): ?string
    {
        return null;
    }

    public function mcpConfigPath(): ?string
    {
        return null;
    }

    public function frontmatter(): bool
    {
        return false;
    }

    public function mcpConfigKey(): string
    {
        return 'mcpServers';
    }

    /** @return array<string, mixed> */
    public function defaultMcpConfig(): array
    {
        return [];
    }

    /**
     * Install MCP server using the appropriate strategy.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        return match ($this->mcpInstallationStrategy()) {
            McpInstallationStrategy::SHELL => $this->installShellMcp($key, $command, $args, $env),
            McpInstallationStrategy::FILE => $this->installFileMcp($key, $command, $args, $env),
            McpInstallationStrategy::NONE => false
        };
    }

    /**
     * Build the HTTP MCP server configuration payload for file-based installation.
     *
     * @return array<string, mixed>
     */
    public function httpMcpServerConfig(string $url): array
    {
        return [
            'type' => 'http',
            'url' => $url,
        ];
    }

    /**
     * Install an HTTP MCP server using the file-based strategy.
     */
    public function installHttpMcp(string $key, string $url): bool
    {
        $path = $this->mcpConfigPath();

        if (! $path) {
            return false;
        }

        $writer = str_ends_with($path, '.toml')
            ? new TomlFileWriter($path, $this->defaultMcpConfig())
            : new FileWriter($path, $this->defaultMcpConfig());

        return $writer
            ->configKey($this->mcpConfigKey())
            ->addServerConfig($key, $this->httpMcpServerConfig($url))
            ->save();
    }

    /**
     * Build the MCP server configuration payload for file-based installation.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     * @return array<string, mixed>
     */
    public function mcpServerConfig(string $command, array $args = [], array $env = []): array
    {
        return [
            'command' => $command,
            'args' => $args,
            'env' => $env,
        ];
    }

    /**
     * Install MCP server using a shell command strategy.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    protected function installShellMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $shellCommand = $this->shellMcpCommand();

        if ($shellCommand === null) {
            return false;
        }

        $normalized = $this->normalizeCommand($command, $args);

        // Build environment string
        $envString = '';

        foreach ($env as $envKey => $value) {
            $envKey = strtoupper($envKey);
            $envString .= "-e {$envKey}=\"{$value}\" ";
        }

        // Replace placeholders in shell command
        $command = str_replace([
            '{key}',
            '{command}',
            '{args}',
            '{env}',
        ], [
            $key,
            $normalized['command'],
            implode(' ', array_map(fn (string $arg): string => '"'.$arg.'"', $normalized['args'])),
            trim($envString),
        ], $shellCommand);

        $result = Process::run($command);

        if ($result->successful()) {
            return true;
        }

        return str_contains($result->errorOutput(), 'already exists');
    }

    /**
     * Install MCP server using a file-based configuration strategy.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    protected function installFileMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $path = $this->mcpConfigPath();

        if (! $path) {
            return false;
        }

        $normalized = $this->normalizeCommand($command, $args);

        $writer = str_ends_with($path, '.toml')
            ? new TomlFileWriter($path, $this->defaultMcpConfig())
            : new FileWriter($path, $this->defaultMcpConfig());

        return $writer
            ->configKey($this->mcpConfigKey())
            ->addServerConfig($key, $this->mcpServerConfig($normalized['command'], $normalized['args'], $env))
            ->save();
    }

    /**
     * Normalize command by splitting space-separated commands into command + args.
     *
     * Absolute paths (starting with / on Unix or a drive letter on Windows)
     * are never split, as they may contain spaces (e.g. macOS "Application Support").
     *
     * @param  array<int, string>  $args
     * @return array{command: string, args: array<int, string>}
     */
    protected function normalizeCommand(string $command, array $args = []): array
    {
        if (str_starts_with($command, '/') || preg_match('#^[a-zA-Z]:[/\\\\]#', $command)) {
            return [
                'command' => $command,
                'args' => $args,
            ];
        }

        $parts = str($command)->explode(' ');

        return [
            'command' => $parts->first(),
            'args' => $parts->skip(1)->values()->merge($args)->all(),
        ];
    }

    /**
     * Post-process the generated guidelines' Markdown.
     */
    public function transformGuidelines(string $markdown): string
    {
        return $markdown;
    }
}
