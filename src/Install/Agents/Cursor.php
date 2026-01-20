<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class Cursor extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'cursor';
    }

    public function displayName(): string
    {
        return 'Cursor';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Cursor.app'],
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/cursor',
                    '/usr/local/bin/cursor',
                    '~/.local/bin/cursor',
                ],
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Cursor',
                    '%LOCALAPPDATA%\\Programs\\Cursor',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.cursor'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.cursor/mcp.json';
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.cursor.guidelines_path', '.cursor/rules/laravel-boost.mdc');
    }

    public function frontmatter(): bool
    {
        return true;
    }

    public function skillsPath(): string
    {
        return config('boost.agents.cursor.skills_path', '.cursor/skills');
    }
}
