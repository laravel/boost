<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agent;

use Laravel\Boost\Contracts\SupportGuidelines;
use Laravel\Boost\Contracts\SupportMCP;
use Laravel\Boost\Install\Enums\Platform;

class Junie extends Agent implements SupportGuidelines, SupportMCP
{
    public function name(): string
    {
        return 'junie';
    }

    public function displayName(): string
    {
        return 'Junie';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/PhpStorm.app'],
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/phpstorm',
                    '/opt/PhpStorm*',
                    '/usr/local/bin/phpstorm',
                    '~/.local/share/JetBrains/Toolbox/apps/PhpStorm/ch-*',
                ],
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\JetBrains\\PhpStorm*',
                    '%LOCALAPPDATA%\\JetBrains\\Toolbox\\apps\\PhpStorm\\ch-*',
                    '%LOCALAPPDATA%\\Programs\\PhpStorm',
                ],
            ],
        };
    }

    public function useAbsolutePathForMcp(): bool
    {
        return true;
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.idea', '.junie'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.junie/mcp/mcp.json';
    }

    public function guidelinesPath(): string
    {
        return config('boost.code_environments.phpstorm.guidelines_path', '.junie/guidelines.md');
    }
}
