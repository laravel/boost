<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class Antigravity extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'antigravity';
    }

    public function displayName(): string
    {
        return 'Google Antigravity';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v antigravity',
            ],
            Platform::Windows => [
                'command' => 'cmd /c where antigravity 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.agents'],
            'files' => ['.agents/plugins/laravel-boost/plugin.json'],
        ];
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    public function mcpConfigPath(): string
    {
        return config('boost.agents.antigravity.mcp_config_path', '.agents/plugins/laravel-boost/mcp_config.json');
    }

    public function mcpConfigKey(): string
    {
        return 'mcpServers';
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.antigravity.guidelines_path', '.agents/rules/boost.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.antigravity.skills_path', '.agents/skills');
    }
}
