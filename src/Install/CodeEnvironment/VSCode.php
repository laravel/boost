<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\Platform;

class VSCode extends CodeEnvironment implements McpClient
{
    public function name(): string
    {
        return 'vscode';
    }

    public function displayName(): string
    {
        return 'VS Code';
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
            'paths' => ['.vscode'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.vscode/mcp.json';
    }

    public function mcpConfigKey(): string
    {
        return 'servers';
    }
}
