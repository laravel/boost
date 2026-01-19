<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agent;

use Laravel\Boost\Contracts\SupportGuidelines;
use Laravel\Boost\Contracts\SupportMCP;
use Laravel\Boost\Contracts\SupportSkills;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class ClaudeCode extends Agent implements SupportGuidelines, SupportMCP, SupportSkills
{
    public function name(): string
    {
        return 'claude_code';
    }

    public function displayName(): string
    {
        return 'Claude Code';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v claude',
            ],
            Platform::Windows => [
                'command' => 'where claude 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.claude'],
            'files' => ['CLAUDE.md'],
        ];
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    public function mcpConfigPath(): string
    {
        return '.mcp.json';
    }

    public function guidelinesPath(): string
    {
        return config('boost.code_environments.claude_code.guidelines_path', 'CLAUDE.md');
    }

    public function skillsPath(): string
    {
        return config('boost.code_environments.claude_code.skills_path', '.claude/skills');
    }
}
