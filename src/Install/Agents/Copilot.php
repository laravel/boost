<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class Copilot extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'copilot';
    }

    public function displayName(): string
    {
        return 'GitHub Copilot';
    }

    public function detectOnSystem(Platform $platform): bool
    {
        return false;
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Visual Studio Code.app'],
            ],
            Platform::Linux => [
                'command' => 'command -v code',
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Microsoft VS Code',
                    '%LOCALAPPDATA%\\Programs\\Microsoft VS Code',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.vscode'],
            'files' => ['.github/copilot-instructions.md'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.vscode/mcp.json';
    }

    public function mcpConfigKey(): string
    {
        return 'servers';
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.copilot.guidelines_path', 'AGENTS.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.copilot.skills_path', '.github/skills');
    }
}
