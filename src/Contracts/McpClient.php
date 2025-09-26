<?php

declare(strict_types=1);

namespace Laravel\Boost\Contracts;

/**
 * Contract for code editors that support MCP (Model Context Protocol).
 */
interface McpClient
{
    public function name(): string;

    /**
     * Get the display name of the MCP (Model Context Protocol) client.
     */
    public function mcpClientName(): ?string;

    /**
     * Whether to use absolute paths for MCP commands.
     */
    public function useAbsolutePathForMcp(): bool;

    /**
     * Get the PHP executable path for this MCP client.
     */
    public function getPhpPath(bool $forceAbsolutePath = false): string;

    /**
     * Get the artisan path for this MCP client.
     */
    public function getArtisanPath(bool $forceAbsolutePath = false): string;

    /**
     * Install an MCP server configuration in this IDE.
     *
     * @param  string  $key  Server identifier/name
     * @param  string  $command  Executable command to run the MCP server
     * @param  array<int, string>  $args  Command line arguments
     * @param  array<string, string>  $env  Environment variables
     * @return bool True if installation succeeded, false otherwise
     */
    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool;
}
