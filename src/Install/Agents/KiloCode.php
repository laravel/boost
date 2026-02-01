<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class KiloCode extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'kilo_code';
    }

    public function displayName(): string
    {
        return 'Kilo Code';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v kilo 2>/dev/null || command -v kilo-code 2>/dev/null',
            ],
            Platform::Windows => [
                'command' => 'where kilo 2>nul || where kilo-code 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.kilocode'],
            'files' => ['AGENTS.md'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.kilocode/mcp.json';
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.kilo_code.guidelines_path', '.kilocode/rules');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.kilo_code.skills_path', '.kilocode/skills');
    }

    public function frontmatter(): bool
    {
        return true;
    }
}
