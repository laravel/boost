<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class OpenCode extends CodeEnvironment implements Agent, McpClient
{
    public function name(): string
    {
        return 'opencode';
    }

    public function displayName(): string
    {
        return 'OpenCode';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v opencode',
            ],
            Platform::Windows => [
                'command' => 'where opencode 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'files' => ['AGENTS.md', 'opencode.json'],
        ];
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    public function mcpConfigPath(): string
    {
        return 'opencode.json';
    }

    public function guidelinesPath(): string
    {
        return config('boost.code_environments.opencode.guidelines_path', 'AGENTS.md');
    }

    public function mcpConfigKey(): string
    {
        return 'mcp';
    }

    /** {@inheritDoc} */
    public function defaultMcpConfig(): array
    {
        return [
            '$schema' => 'https://opencode.ai/config.json',
        ];
    }

    /** {@inheritDoc} */
    public function mcpServerConfig(string $command, array $args = [], array $env = []): array
    {
        return [
            'type' => 'local',
            'enabled' => true,
            'command' => [$command, ...$args],
            'environment' => $env,
        ];
    }
}
