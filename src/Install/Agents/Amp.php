<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Illuminate\Support\Facades\File;
use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class Amp extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'amp';
    }

    public function displayName(): string
    {
        return 'Amp';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v amp',
                'paths' => ['~/.amp', '~/.config/amp'],
            ],
            Platform::Windows => [
                'command' => 'cmd /c where amp 2>nul',
                'paths' => ['%USERPROFILE%\\.amp', '%USERPROFILE%\\.config\\amp'],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.amp'],
        ];
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    /**
     * Install MCP server directly in .amp/settings.json.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $normalized = $this->normalizeCommand($command, $args);

        $serverConfig = collect([
            'command' => $normalized['command'],
            'args' => $normalized['args'],
            'env' => $env,
        ])->filter(fn ($value): bool => ! in_array($value, [[], null, ''], true))->toArray();

        return $this->writeToSettingsFile($key, $serverConfig);
    }

    /** {@inheritDoc} */
    public function installHttpMcp(string $key, string $url): bool
    {
        return $this->writeToSettingsFile($key, ['url' => $url]);
    }

    /**
     * Write MCP server config directly to .amp/settings.json, updating existing keys.
     *
     * Bypasses FileWriter because Amp's config key "amp.mcpServers" contains
     * a dot, which conflicts with Laravel's data_set() dot-notation.
     *
     * @param  array<string, mixed>  $serverConfig
     */
    protected function writeToSettingsFile(string $key, array $serverConfig): bool
    {
        $path = base_path('.amp/settings.json');

        File::ensureDirectoryExists(dirname($path));

        $config = $this->readSettingsFile($path);

        if ($config === null) {
            return false;
        }

        $config['amp.mcpServers'] ??= [];
        $config['amp.mcpServers'][$key] = $serverConfig;

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return is_string($json) && File::put($path, str_replace("\r\n", "\n", $json)) !== false;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function readSettingsFile(string $path): ?array
    {
        if (! File::exists($path) || File::size($path) < 3) {
            return [];
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.amp.guidelines_path', 'AGENTS.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.amp.skills_path', '.agents/skills');
    }
}
