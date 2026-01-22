<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\Platform;

class TraeCN extends CodeEnvironment implements McpClient
{
    public function name(): string
    {
        return 'traecn';
    }

    public function displayName(): string
    {
        return 'Trae CN';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Trae CN.app'],
            ],
            Platform::Linux => [
                'command' => 'command -v trae',
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Trae CN',
                    '%LOCALAPPDATA%\\Programs\\Trae CN',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.trae'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.trae/mcp.json';
    }

    public function mcpConfigKey(): string
    {
        return 'mcpServers';
    }
}
