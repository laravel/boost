<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class Antigravity extends Agent implements SupportsGuidelines, SupportsSkills
{
    public function name(): string
    {
        return 'antigravity';
    }

    public function displayName(): string
    {
        return 'Antigravity';
    }

    public function transformGuidelines(string $markdown): string
    {
        return preg_replace_callback(
            '/## Foundational Context.*?(?=\n## |$)/s',
            fn (array $matches) => preg_replace('/(?<!\\\\)@([a-z0-9-]+\/[a-z0-9-]+)/i', '\\\\@$1', $matches[0]),
            $markdown
        );
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v antigravity',
            ],
            Platform::Windows => [
                'command' => 'where antigravity 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.agent'],
            'files' => ['AGENT.md'],
        ];
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.antigravity.guidelines_path', 'AGENT.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.antigravity.skills_path', '.agent/skills');
    }
}
