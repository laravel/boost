<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class Factory extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'factory';
    }

    public function displayName(): string
    {
        return 'Factory Droid';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v droid',
                'paths' => ['~/.factory'],
            ],
            Platform::Windows => [
                'command' => 'cmd /c where droid 2>nul',
                'paths' => ['%USERPROFILE%\\.factory'],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.factory'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return config('boost.agents.factory.mcp_config_path', '.factory/mcp.json');
    }

    /** {@inheritDoc} */
    public function mcpServerConfig(string $command, array $args = [], array $env = []): array
    {
        return collect([
            'type' => 'stdio',
            'command' => $command,
            'args' => $args,
            'env' => $env,
        ])->filter(fn ($value): bool => ! in_array($value, [[], null, ''], true))
            ->toArray();
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.factory.guidelines_path', 'AGENTS.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.factory.skills_path', '.factory/skills');
    }
}
