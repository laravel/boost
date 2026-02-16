<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\Platform;

class Gemini extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'gemini';
    }

    public function displayName(): string
    {
        return 'Gemini CLI';
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
                'command' => 'command -v gemini',
            ],
            Platform::Windows => [
                'command' => 'cmd /c where gemini 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.gemini'],
            'files' => ['GEMINI.md'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.gemini/settings.json';
    }

    /** {@inheritDoc} */
    public function httpMcpServerConfig(string $url): array
    {
        return [
            'command' => 'npx',
            'args' => ['-y', 'mcp-remote', $url],
        ];
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.gemini.guidelines_path', 'GEMINI.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.gemini.skills_path', '.agents/skills');
    }
}
