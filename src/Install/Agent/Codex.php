<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agent;

use Laravel\Boost\Contracts\SupportGuidelines;
use Laravel\Boost\Contracts\SupportMCP;
use Laravel\Boost\Contracts\SupportSkills;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class Codex extends Agent implements SupportGuidelines, SupportMCP, SupportSkills
{
    public function name(): string
    {
        return 'codex';
    }

    public function displayName(): string
    {
        return 'Codex CLI';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'which codex',
            ],
            Platform::Windows => [
                'command' => 'where codex 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.codex'],
            'files' => ['AGENTS.md'],
        ];
    }

    public function guidelinesPath(): string
    {
        return config('boost.code_environments.codex.guidelines_path', 'AGENTS.md');
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::SHELL;
    }

    public function shellMcpCommand(): string
    {
        return 'codex mcp add {key} -- {command} {args}';
    }

    public function skillsPath(): string
    {
        return config('boost.code_environments.codex.skills_path', '.codex/skills');
    }
}
