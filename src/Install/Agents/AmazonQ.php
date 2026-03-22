<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class AmazonQ extends Agent implements SupportsGuidelines, SupportsSkills
{
    public function name(): string
    {
        return 'amazonq';
    }

    public function displayName(): string
    {
        return 'Amazon Q Developer';
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
            'paths' => ['.amazonq'],
        ];
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.amazonq.guidelines_path', '.amazonq/rules/guidelines.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.amazonq.skills_path', '.amazonq/rules/skills');
    }
}
