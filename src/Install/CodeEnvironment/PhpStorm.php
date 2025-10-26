<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\Platform;

class PhpStorm extends CodeEnvironment implements Agent, McpClient
{
    public bool $useAbsolutePathForMcp = true;

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

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.idea', '.junie'],
        ];
    }

    public function agentName(): string
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

    public function getPhpPath(bool $forceAbsolutePath = false): string
    {
        if ($this->isSailProject()) {
            return 'bash';
        }

        return parent::getPhpPath($forceAbsolutePath);
    }

    public function getArtisanPath(bool $forceAbsolutePath = false): string
    {
        if ($this->isSailProject()) {
            return '-lc';
        }

        return parent::getArtisanPath($forceAbsolutePath);
    }

    /**
     * Install MCP server for PhpStorm with special handling for Sail projects.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        if ($this->isSailProject()) {
            // For Sail projects, override args to use docker exec command
            $args = ['docker exec -i "$(docker ps -q --filter label=com.docker.compose.service=laravel.test | head -n1)" php /var/www/html/artisan boost:mcp'];
        }

        return parent::installMcp($key, $command, $args, $env);
    }
}
