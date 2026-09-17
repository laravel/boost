<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class Omp extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'omp';
    }

    public function displayName(): string
    {
        return 'Oh My Pi';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v omp',
                'paths' => ['~/.omp'],
            ],
            Platform::Windows => [
                'command' => 'cmd /c where omp 2>nul',
                'paths' => ['%USERPROFILE%\\.omp'],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.omp'],
            'files' => ['.omp/mcp.json'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return config('boost.agents.omp.mcp_config_path', '.omp/mcp.json');
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
        return config('boost.agents.omp.guidelines_path', 'AGENTS.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.omp.skills_path', '.omp/skills');
    }
}
