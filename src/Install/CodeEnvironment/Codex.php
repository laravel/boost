<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\Platform;

class Codex extends CodeEnvironment implements Agent, McpClient
{
    public function name(): string
    {
        return 'codex';
    }

    public function displayName(): string
    {
        return 'Codex';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'which codex',
            ],
            Platform::Windows => [
                'command' => 'where codex 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        $paths = ['.codex'];
        $files = ['.codex/config.json', '.codex/mcp.json'];

        // Allow environment-driven config locations when running in CI or custom setups.
        $codexHome = getenv('CODEX_HOME') ?: null;
        if ($codexHome) {
            $paths[] = rtrim($codexHome, DIRECTORY_SEPARATOR).'/.codex';
        }

        $codexConfigDir = getenv('CODEX_CONFIG_DIR') ?: null;
        if ($codexConfigDir) {
            $paths[] = rtrim($codexConfigDir, DIRECTORY_SEPARATOR);
            $files[] = rtrim($codexConfigDir, DIRECTORY_SEPARATOR).'/mcp.json';
            $files[] = rtrim($codexConfigDir, DIRECTORY_SEPARATOR).'/config.json';
        }

        return [
            'paths' => $paths,
            'files' => $files,
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.codex/mcp.json';
    }

    public function guidelinesPath(): string
    {
        return '.codex/guidelines.md';
    }

    public function frontmatter(): bool
    {
        return true;
    }
}
