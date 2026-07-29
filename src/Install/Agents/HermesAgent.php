<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class HermesAgent extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'hermes_agent';
    }

    public function displayName(): string
    {
        return 'Hermes Agent';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v hermes',
            ],
            Platform::Windows => [
                'command' => 'cmd /c where hermes 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'files' => ['.hermes.md', 'HERMES.md'],
        ];
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    public function mcpConfigPath(): string
    {
        return config('boost.agents.hermes_agent.mcp_config_path', '.mcp.json');
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.hermes_agent.guidelines_path', 'HERMES.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.hermes_agent.skills_path', '.hermes/skills');
    }
}
