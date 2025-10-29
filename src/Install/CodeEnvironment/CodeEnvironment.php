<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Illuminate\Support\Facades\Process;
use Laravel\Boost\BoostManager;
use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;
use Laravel\Boost\Install\Mcp\FileWriter;

abstract class CodeEnvironment
{
    public bool $useAbsolutePathForMcp = false;

    public function __construct(protected readonly DetectionStrategyFactory $strategyFactory) {}

    abstract public function name(): string;

    abstract public function displayName(): string;

    public function agentName(): ?string
    {
        return $this->displayName();
    }

    public function mcpClientName(): ?string
    {
        return $this->displayName();
    }

    public function useAbsolutePathForMcp(): bool
    {
        return $this->useAbsolutePathForMcp;
    }

    public function getPhpPath(bool $forceAbsolutePath = false): string
    {
        return ($this->useAbsolutePathForMcp() || $forceAbsolutePath) ? PHP_BINARY : 'php';
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

    public function isAgent(): bool
    {
        return $this->agentName() && $this instanceof Agent;
    }

    public function isMcpClient(): bool
    {
        return $this->mcpClientName() && $this instanceof McpClient;
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    public static function fromName(string $name): ?CodeEnvironment
    {
        $detectionFactory = app(DetectionStrategyFactory::class);
        $boostManager = app(BoostManager::class);

        foreach ($boostManager->getCodeEnvironments() as $class) {
            /** @var class-string<CodeEnvironment> $class */
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
            $command,
            implode(' ', array_map(fn (string $arg): string => '"'.$arg.'"', $args)),
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

        return (new FileWriter($path, $this->defaultMcpConfig()))
            ->configKey($this->mcpConfigKey())
            ->addServerConfig($key, $this->mcpServerConfig($command, $args, $env))
            ->save();
    }
}
