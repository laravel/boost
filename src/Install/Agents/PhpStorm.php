<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Guideline;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\Platform;

class PhpStorm extends Agent implements Guideline, McpClient
{
    public function name(): string
    {
        return 'phpstorm';
    }

    public function displayName(): string
    {
        return 'PhpStorm';
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

    public function guidelineProviderName(): string
    {
        return 'Junie';
    }

    public function mcpConfigPath(): string
    {
        return '.junie/mcp/mcp.json';
    }

    public function guidelinesPath(): string
    {
        return '.junie/guidelines.md';
    }
}
