<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class Amp extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'amp';
    }

    public function displayName(): string
    {
        return 'Amp';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v amp',
            ],
            Platform::Windows => [
                'command' => 'where amp 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.amp'],
            'files' => ['AGENTS.md'],
        ];
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    public function mcpConfigPath(): string
    {
        return '.amp/settings.json';
    }

    public function mcpConfigKey(): string
    {
        return 'amp.mcpServers';
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.amp.guidelines_path', 'AGENTS.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.amp.skills_path', '.agents/skills');
    }
}
