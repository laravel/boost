<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class ZCode extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'zcode';
    }

    public function displayName(): string
    {
        return 'ZCode';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'paths' => ['~/.zcode'],
            ],
            Platform::Windows => [
                'paths' => ['%USERPROFILE%\\.zcode'],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.zcode'],
            'files' => ['.zcode/config.json'],
        ];
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.zcode.guidelines_path', 'AGENTS.md');
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    public function mcpConfigPath(): string
    {
        return config('boost.agents.zcode.mcp_config_path', '.zcode/config.json');
    }

    public function mcpConfigKey(): string
    {
        return 'mcp.servers';
    }

    public function skillsPath(): string
    {
        return config('boost.agents.zcode.skills_path', '.zcode/skills');
    }
}
