<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Mcp;

use Illuminate\Support\Facades\File;

class TomlFileWriter
{
    protected string $configKey = 'mcp_servers';

    /** @var array<string, array<string, mixed>> */
    protected array $serversToAdd = [];

    /** @param array<string, mixed> $baseConfig */
    public function __construct(protected string $filePath, protected array $baseConfig = [])
    {
        //
    }

    public function configKey(string $key): self
    {
        $this->configKey = $key;

        return $this;
    }

    /** @param array<string, mixed> $config */
    public function addServerConfig(string $key, array $config): self
    {
        $this->serversToAdd[$key] = collect($config)
            ->filter(fn ($value): bool => ! in_array($value, [[], null, ''], true))
            ->toArray();

        return $this;
    }

    public function save(): bool
    {
        File::ensureDirectoryExists(dirname($this->filePath));

        if ($this->shouldWriteNew()) {
            return $this->createNewFile();
        }

        return $this->updateExistingFile();
    }

    protected function createNewFile(): bool
    {
        $lines = [];

        foreach ($this->baseConfig as $key => $value) {
            if (! is_array($value)) {
                $lines[] = "{$key} = ".$this->formatValue($value);
            }
        }

        foreach ($this->serversToAdd as $key => $config) {
            if ($lines !== []) {
                $lines[] = '';
            }

            $lines[] = $this->buildServerToml($key, $config);
        }

        return $this->writeFile(implode(PHP_EOL, $lines).PHP_EOL);
    }

    protected function updateExistingFile(): bool
    {
        $content = File::get($this->filePath);

        foreach ($this->serversToAdd as $key => $config) {
            if ($this->serverExists($content, $key)) {
                $content = $this->removeExistingServer($content, $key);
            }

            $trimmed = rtrim($content);
            $separator = $trimmed === '' ? '' : PHP_EOL.PHP_EOL;
            $content = $trimmed.$separator.$this->buildServerToml($key, $config).PHP_EOL;
        }

        return $this->writeFile($content);
    }

    /** @param array<string, mixed> $config */
    protected function buildServerToml(string $key, array $config): string
    {
        $lines = [];
        $lines[] = "[{$this->configKey}.{$key}]";

        foreach ($config as $field => $value) {
            if ($field === 'env' && is_array($value)) {
                continue;
            }

            $lines[] = "{$field} = ".$this->formatValue($value);
        }

        if (isset($config['env']) && is_array($config['env']) && $config['env'] !== []) {
            $lines[] = '';
            $lines[] = "[{$this->configKey}.{$key}.env]";

            foreach ($config['env'] as $envKey => $envValue) {
                $lines[] = "{$envKey} = ".$this->formatValue($envValue);
            }
        }

        return implode(PHP_EOL, $lines);
    }

    protected function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            return '"'.$this->escapeTomlString($value).'"';
        }

        if (is_array($value)) {
            $items = array_map($this->formatValue(...), $value);

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

    protected function serverExists(string $content, string $key): bool
    {
        $pattern = '/^\['.preg_quote($this->configKey, '/').'\.'.preg_quote($key, '/').'\]/m';

        return (bool) preg_match($pattern, $content);
    }

    protected function removeExistingServer(string $content, string $key): string
    {
        $escapedConfigKey = preg_quote($this->configKey, '/');
        $escapedKey = preg_quote($key, '/');

        $envPattern = '/(\r?\n)*\['.$escapedConfigKey.'\.'.$escapedKey.'\.env\].*?(?=\r?\n\[|$)/s';
        $content = preg_replace($envPattern, '', $content) ?? $content;

        $mainPattern = '/(\r?\n)*\['.$escapedConfigKey.'\.'.$escapedKey.'\].*?(?=\r?\n\[|$)/s';

        return preg_replace($mainPattern, '', $content) ?? $content;
    }

    protected function shouldWriteNew(): bool
    {
        if (! File::exists($this->filePath)) {
            return true;
        }

        return File::size($this->filePath) < 3;
    }

    protected function writeFile(string $content): bool
    {
        return File::put($this->filePath, $content) !== false;
    }
}
