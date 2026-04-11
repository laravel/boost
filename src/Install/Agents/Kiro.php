<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class Kiro extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'kiro';
    }

    public function displayName(): string
    {
        return 'Kiro';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Kiro.app'],
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/kiro',
                    '/usr/local/bin/kiro',
                    '~/.local/bin/kiro',
                ],
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Kiro',
                    '%LOCALAPPDATA%\\Programs\\Kiro',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.kiro'],
        ];
    }

    public function frontmatter(): bool
    {
        return true;
    }

    public function mcpConfigPath(): string
    {
        return config('boost.agents.kiro.mcp_config_path', '.kiro/settings/mcp.json');
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.kiro.guidelines_path', '.kiro/steering/boost.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.kiro.skills_path', '.kiro/skills');
    }
}
