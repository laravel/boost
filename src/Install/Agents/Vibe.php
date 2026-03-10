<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Illuminate\Support\Facades\File;
use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class Vibe extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'vibe';
    }

    public function displayName(): string
    {
        return 'Mistral Vibe';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v vibe',
            ],
            Platform::Windows => [
                'command' => 'cmd /c where vibe 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.vibe'],
            'files' => ['.vibe/config.toml'],
        ];
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
    }

    public function mcpConfigPath(): string
    {
        return '.vibe/config.toml';
    }

    public function mcpConfigKey(): string
    {
        return 'mcp_servers';
    }

    /** {@inheritDoc} */
    public function httpMcpServerConfig(string $url): array
    {
        return [
            'transport' => 'http',
            'url' => $url,
        ];
    }

    /** {@inheritDoc} */
    public function mcpServerConfig(string $command, array $args = [], array $env = []): array
    {
        return collect([
            'transport' => 'stdio',
            'command' => $command,
            'args' => $args,
            'env' => $env,
        ])->filter(fn ($value): bool => ! in_array($value, [[], null, ''], true))
            ->toArray();
    }

    /**
     * Install MCP server using Vibe's [[mcp_servers]] TOML array-of-tables format.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    protected function installFileMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $normalized = $this->normalizeCommand($command, $args);

        return $this->writeVibeServerBlock($key, $this->mcpServerConfig($normalized['command'], $normalized['args'], $env));
    }

    /**
     * Install an HTTP MCP server using Vibe's [[mcp_servers]] TOML array-of-tables format.
     */
    public function installHttpMcp(string $key, string $url): bool
    {
        return $this->writeVibeServerBlock($key, $this->httpMcpServerConfig($url));
    }

    /**
     * Write a [[mcp_servers]] block to the Vibe config file.
     *
     * @param  array<string, mixed>  $config
     */
    protected function writeVibeServerBlock(string $key, array $config): bool
    {
        $path = $this->mcpConfigPath();

        File::ensureDirectoryExists(dirname($path));

        $content = '';

        if (File::exists($path) && File::size($path) >= 3) {
            $content = File::get($path);
            $content = $this->removeVibeServer($content, $key);
        }

        $block = $this->buildVibeTomlBlock($key, $config);
        $trimmed = rtrim($content);
        $separator = $trimmed === '' ? '' : PHP_EOL.PHP_EOL;
        $content = $trimmed.$separator.$block.PHP_EOL;

        return File::put($path, $content) !== false;
    }

    /**
     * Build a [[mcp_servers]] TOML block for a server.
     *
     * @param  array<string, mixed>  $config
     */
    protected function buildVibeTomlBlock(string $key, array $config): string
    {
        $lines = [];
        $lines[] = '[[mcp_servers]]';
        $lines[] = 'name = "'.$this->escapeTomlString($key).'"';

        $envData = [];

        foreach ($config as $field => $value) {
            if ($field === 'env' && is_array($value)) {
                $envData = $value;

                continue;
            }

            $lines[] = $field.' = '.$this->formatTomlValue($value);
        }

        if ($envData !== []) {
            foreach ($envData as $envKey => $envValue) {
                $lines[] = $envKey.' = '.$this->formatTomlValue($envValue);
            }
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Remove an existing [[mcp_servers]] block with the given name.
     */
    protected function removeVibeServer(string $content, string $key): string
    {
        $escapedKey = preg_quote($key, '/');
        $pattern = '/(\r?\n)*\[\[mcp_servers\]\]\s*\r?\nname\s*=\s*"'.$escapedKey.'".*?(?=\r?\n\[\[|\r?\n\[(?!\[)|\Z)/s';

        return preg_replace($pattern, '', $content) ?? $content;
    }

    protected function formatTomlValue(mixed $value): string
    {
        if (is_string($value)) {
            return '"'.$this->escapeTomlString($value).'"';
        }

        if (is_array($value)) {
            $items = array_map($this->formatTomlValue(...), $value);

            return '['.implode(', ', $items).']';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    protected function escapeTomlString(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            '"' => '\\"',
            "\n" => '\\n',
            "\r" => '\\r',
            "\t" => '\\t',
        ]);
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.vibe.guidelines_path', 'AGENTS.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.vibe.skills_path', '.agents/skills');
    }
}
