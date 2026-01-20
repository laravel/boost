<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use RuntimeException;

class Docker
{
    protected ?string $containerName = null;

    /**
     * Check if Docker Compose is available without Laravel Sail.
     */
    public function isAvailableWithoutSail(): bool
    {
        $hasSail = file_exists(base_path('vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'sail'));
        $hasDockerCompose = file_exists(base_path('docker-compose.yml')) || file_exists(base_path('compose.yaml'));

        return ! $hasSail && $hasDockerCompose;
    }

    /**
     * Set the container name to use for Docker commands.
     */
    public function setContainerName(string $containerName): self
    {
        $this->containerName = $containerName;

        return $this;
    }

    /**
     * Get the configured container name.
     */
    public function getContainerName(): ?string
    {
        return $this->containerName;
    }

    /**
     * Check if a container name has been configured.
     */
    public function hasContainerName(): bool
    {
        return $this->containerName !== null && $this->containerName !== '';
    }

    /**
     * Build the MCP command for generic Docker execution.
     *
     * @return array<int, string>
     */
    public function buildMcpCommand(string $serverName): array
    {
        if (! $this->hasContainerName()) {
            throw new RuntimeException('Docker container name has not been configured.');
        }

        return [$serverName, 'docker', 'exec', '-i', $this->containerName, 'php', 'artisan', 'boost:mcp'];
    }

    /**
     * Build a generic command to run inside the Docker container.
     */
    public function command(string $command): string
    {
        if (! $this->hasContainerName()) {
            throw new RuntimeException('Docker container name has not been configured.');
        }

        return sprintf('docker exec -i %s %s', $this->containerName, $command);
    }

    /**
     * Build the artisan command for Docker execution.
     */
    public function artisanCommand(): string
    {
        return $this->command('php artisan');
    }

    /**
     * Build the composer command for Docker execution.
     */
    public function composerCommand(): string
    {
        return $this->command('composer');
    }
}
