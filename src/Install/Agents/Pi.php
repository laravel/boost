<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class Pi extends Agent implements SupportsGuidelines, SupportsSkills
{
    public function name(): string
    {
        return 'pi';
    }

    public function displayName(): string
    {
        return 'Pi';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'which pi',
            ],
            Platform::Windows => [
                'command' => 'cmd /c where pi 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.pi'],
            'files' => ['.pi/settings.json'],
        ];
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.pi.guidelines_path', 'AGENTS.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.pi.skills_path', '.pi/skills');
    }
}
