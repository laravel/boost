<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
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
                'paths' => ['~/.amp', '~/.config/amp'],
            ],
            Platform::Windows => [
                'command' => 'cmd /c where amp 2>nul',
                'paths' => ['%USERPROFILE%\\.amp', '%USERPROFILE%\\.config\\amp'],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.amp'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return base_path('.amp/settings.json');
    }

    public function mcpConfigKey(): string
    {
        return 'amp.mcpServers';
    }

    /** {@inheritDoc} */
    public function httpMcpServerConfig(string $url): array
    {
        return ['url' => $url];
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
