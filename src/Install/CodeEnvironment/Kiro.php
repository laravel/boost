<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\Platform;

/**
 * Kiro IDE code environment implementation.
 *
 * Kiro is an AI-powered IDE that supports the Model Context Protocol (MCP).
 * This implementation configures Boost to work seamlessly with Kiro by:
 * - Detecting Kiro installations on the system
 * - Configuring MCP settings at .kiro/settings/mcp.json
 * - Creating AI guidelines at .kiro/steering/laravel-boost.md
 * - Supporting Kiro as both an editor and an AI agent
 */
class Kiro extends CodeEnvironment implements Agent, McpClient
{
    /**
     * Get the internal name identifier for Kiro.
     */
    public function name(): string
    {
        return 'kiro';
    }

    /**
     * Get the display name for Kiro shown to users.
     */
    public function displayName(): string
    {
        return 'Kiro';
    }

    /**
     * Get system-level detection configuration for Kiro.
     *
     * Defines the paths where Kiro might be installed on different platforms.
     */
    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Kiro.app'],
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/kiro',
                    '/usr/local/bin/kiro',
                    '~/.local/bin/kiro',
                ],
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Kiro',
                    '%LOCALAPPDATA%\\Programs\\Kiro',
                ],
            ],
        };
    }

    /**
     * Get project-level detection configuration for Kiro.
     *
     * Kiro projects are identified by the presence of a .kiro directory.
     */
    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.kiro'],
        ];
    }

    /**
     * Get the path to Kiro's MCP configuration file.
     */
    public function mcpConfigPath(): string
    {
        return '.kiro/settings/mcp.json';
    }

    /**
     * Get the path where AI guidelines should be created for Kiro.
     *
     * Kiro uses the steering directory for AI guidelines that are
     * automatically loaded to provide context-aware assistance.
     */
    public function guidelinesPath(): string
    {
        return '.kiro/steering/laravel-boost.md';
    }

    /**
     * Determine if Kiro guidelines should include YAML frontmatter.
     *
     * Kiro supports frontmatter for controlling when guidelines are included.
     */
    public function frontmatter(): bool
    {
        return false;
    }
}
