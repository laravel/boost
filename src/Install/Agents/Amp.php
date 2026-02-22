<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
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
        return McpInstallationStrategy::SHELL;
    }

    public function shellMcpCommand(): string
    {
        return 'amp mcp add {key} --workspace -- "{command}" {args}';
    }

    /**
     * Install MCP server, falling back to direct file write when env vars are present.
     *
     * The Amp CLI does not support passing environment variables, so when env
     * vars are provided (e.g. Herd MCP with SITE_PATH), we write directly to
     * .amp/settings.json instead of using the shell command.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        if ($env === []) {
            return parent::installMcp($key, $command, $args, $env);
        }

        return $this->writeToSettingsFile($key, $command, $args, $env);
    }

    /** {@inheritDoc} */
    public function installHttpMcp(string $key, string $url): bool
    {
        $result = Process::run("amp mcp add {$key} --workspace {$url}");

        if ($result->successful()) {
            return true;
        }

        return str_contains($result->errorOutput(), 'already exists');
    }

    /**
     * Write MCP server config directly to .amp/settings.json.
     *
     * Bypasses FileWriter because Amp's config key "amp.mcpServers" contains
     * a dot, which conflicts with Laravel's data_set() dot-notation.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    protected function writeToSettingsFile(string $key, string $command, array $args = [], array $env = []): bool
    {
        $path = base_path('.amp/settings.json');

        File::ensureDirectoryExists(dirname($path));

        $config = File::exists($path) && File::size($path) >= 3
            ? json_decode(File::get($path), true) ?? []
            : [];

        $normalized = $this->normalizeCommand($command, $args);

        $serverConfig = collect([
            'command' => $normalized['command'],
            'args' => $normalized['args'],
            'env' => $env,
        ])->filter(fn ($value): bool => ! in_array($value, [[], null, ''], true))->toArray();

        $config['amp.mcpServers'] ??= [];
        $config['amp.mcpServers'][$key] = $serverConfig;

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $json && File::put($path, str_replace("\r\n", "\n", $json)) !== false;
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
