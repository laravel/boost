<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agent;

use Laravel\Boost\Contracts\SupportGuidelines;
use Laravel\Boost\Contracts\SupportMCP;
use Laravel\Boost\Contracts\SupportSkills;
use Laravel\Boost\Install\Enums\Platform;

class Copilot extends Agent implements SupportGuidelines, SupportMCP, SupportSkills
{
    public function name(): string
    {
        return 'copilot';
    }

    public function displayName(): string
    {
        return 'GitHub Copilot';
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
        return config('boost.code_environments.copilot.guidelines_path', '.github/copilot-instructions.md');
    }

    public function skillsPath(): string
    {
        return config('boost.code_environments.copilot.skills_path', '.github/copilot/skills');
    }
}
