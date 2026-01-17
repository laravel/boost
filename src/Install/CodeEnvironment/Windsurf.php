<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\Platform;

class Windsurf extends CodeEnvironment implements Agent, McpClient
{
    public function name(): string
    {
        return 'windsurf';
    }

    public function displayName(): string
    {
        return 'Windsurf';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Windsurf.app'],
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/windsurf',
                    '/usr/local/bin/windsurf',
                    '~/.local/bin/windsurf',
                ],
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Windsurf',
                    '%LOCALAPPDATA%\\Programs\\Windsurf',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.windsurf'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.windsurf/mcp.json';
    }

    public function guidelinesPath(): string
    {
        return config('boost.code_environments.windsurf.guidelines_path', '.windsurf/rules/laravel-boost.mdc');
    }

    public function frontmatter(): bool
    {
        return true;
    }
}
