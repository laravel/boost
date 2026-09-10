<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Mcp;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use stdClass;

class FileWriter
{
    protected string $configKey = 'mcpServers';

    protected array $serversToAdd = [];

    protected int $defaultIndentation = 8;

    public function __construct(protected string $filePath, protected array $baseConfig = [])
    {
        //
    }

    public function configKey(string $key): self
    {
        $this->configKey = $key;

        return $this;
    }

    /**
     * @deprecated Use addServerConfig() for array-based configuration.
     *
     * @param  array<int, string>  $args
     * @param  array<string, string>  $env
     */
    public function addServer(string $key, string $command, array $args = [], array $env = []): self
    {
        return $this->addServerConfig($key, collect([
            'command' => $command,
            'args' => $args,
            'env' => $env,
        ])->filter(fn ($value): bool => ! in_array($value, [[], null, ''], true))->toArray());
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function addServerConfig(string $key, array $config): self
    {
        $this->serversToAdd[$key] = collect($config)
            ->filter(fn ($value): bool => ! in_array($value, [[], null, ''], true))
            ->toArray();

        return $this;
    }

    public function save(): bool
    {
        $this->ensureDirectoryExists();

        $content = $this->fileExists() ? $this->normalizeContent($this->readFile()) : '';

        // A bare `{}` is rebuilt so baseConfig defaults land in it.
        if ($content === '' || $content === '{}') {
            return $this->createNewFile();
        }

        if ($this->isPlainJson($content)) {
            return $this->updatePlainJsonFile($content);
        }

        if (! $this->hasJson5Features($content)) {
            return false;
        }

        return $this->updateJson5File($content);
    }

    protected function updatePlainJsonFile(string $content): bool
    {
        $config = json_decode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        $this->addServersToConfig($config);

        return $this->writeJsonConfig($config);
    }

    protected function updateJson5File(string $content): bool
    {
        $masked = $this->maskUnquotedComments($content);
        $configKeyPattern = '/["\']'.preg_quote($this->configKey, '/').'["\']\\s*:\\s*\\{/';

        if (preg_match($configKeyPattern, $masked, $matches, PREG_OFFSET_CAPTURE)) {
            return $this->injectIntoExistingConfigKey($content, $masked, $matches);
        }

        return $this->injectNewConfigKey($content, $masked);
    }

    protected function injectIntoExistingConfigKey(string $content, string $masked, array $matches): bool
    {
        $configKeyStart = $matches[0][1];

        $openBracePos = strpos($masked, '{', $configKeyStart);

        if ($openBracePos === false) {
            return false;
        }

        $closeBracePos = $this->findMatchingClosingBrace($masked, $openBracePos);

        if ($closeBracePos === false) {
            return false;
        }

        $serversToAdd = $this->filterExistingServers($masked, $openBracePos, $closeBracePos);

        if ($serversToAdd === []) {
            return true;
        }

        $indentLength = $this->detectIndentation($masked, $closeBracePos);

        $serverJsonParts = [];

        foreach ($serversToAdd as $key => $serverConfig) {
            $serverJsonParts[] = $this->generateServerJson($key, $serverConfig, $indentLength);
        }

        $serversJson = implode(','."\n", $serverJsonParts);

        $insertPos = $this->findInsertionPoint($masked, $openBracePos, $closeBracePos);
        $lineEnd = $insertPos + strcspn($masked, "\n", $insertPos, $closeBracePos - $insertPos);

        $newContent = substr_replace($content, $serversJson, $lineEnd, 0);

        if (! in_array($masked[$insertPos - 1], ['{', ','], true)) {
            $newContent = substr_replace($newContent, ',', $insertPos, 0);
        }

        return $this->writeFile($newContent);
    }

    protected function filterExistingServers(string $content, int $openBracePos, int $closeBracePos): array
    {
        $configContent = substr($content, $openBracePos + 1, $closeBracePos - $openBracePos - 1);
        $filteredServers = [];

        foreach ($this->serversToAdd as $key => $serverConfig) {
            if (! $this->serverExistsInContent($configContent, $key)) {
                $filteredServers[$key] = $serverConfig;
            }
        }

        return $filteredServers;
    }

    protected function serverExistsInContent(string $content, string $serverKey): bool
    {
        $quotedPattern = '/["\']'.preg_quote($serverKey, '/').'["\']\\s*:/';
        $unquotedPattern = '/(?<=^|\\s|,|{)'.preg_quote($serverKey, '/').'\\s*:/m';

        return preg_match($quotedPattern, $content) || preg_match($unquotedPattern, $content);
    }

    protected function maskUnquotedComments(string $content): string
    {
        // Match quoted strings (keep) or line and block comments (blank out, keeping length and line breaks)
        $pattern = '/"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|\\/\\/[^\\n]*|\\/\\*[\\s\\S]*?\\*\\//';

        return preg_replace_callback(
            $pattern,
            fn (array $matches): string => Str::startsWith($matches[0], ['//', '/*'])
                ? (string) preg_replace('/[^\n]/', ' ', $matches[0])
                : $matches[0],
            $content
        ) ?? $content;
    }

    protected function injectNewConfigKey(string $content, string $masked): bool
    {
        $openBracePos = strpos($masked, '{');

        if ($openBracePos === false) {
            return false;
        }

        $serverJsonParts = [];

        foreach ($this->serversToAdd as $key => $serverConfig) {
            $serverJsonParts[] = $this->generateServerJson($key, $serverConfig);
        }

        $serversJson = implode(',', $serverJsonParts);
        $configKeySection = '"'.$this->configKey.'": {'.$serversJson.'}';

        $needsComma = $this->needsCommaAfterBrace($masked, $openBracePos);
        $injection = $configKeySection.($needsComma ? ',' : '');

        $newContent = substr_replace($content, $injection, $openBracePos + 1, 0);

        return $this->writeFile($newContent);
    }

    protected function generateServerJson(string $key, array $serverConfig, int $baseIndent = 0): string
    {
        $json = json_encode($serverConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $json = str_replace("\r\n", "\n", $json);

        if (empty($baseIndent)) {
            return '"'.$key.'": '.$json;
        }

        $baseIndent = str_repeat(' ', $baseIndent);
        $lines = explode("\n", $json);
        $firstLine = array_shift($lines);
        $indentedLines = [
            "{$baseIndent}\"{$key}\": {$firstLine}",
            ...array_map(fn (string $line): string => $baseIndent.$line, $lines),
        ];

        return "\n".implode("\n", $indentedLines);
    }

    protected function needsCommaAfterBrace(string $content, int $bracePosition): bool
    {
        $trimmed = ltrim(substr($content, $bracePosition + 1));

        return filled($trimmed) && ! Str::startsWith($trimmed, '}');
    }

    protected function findMatchingClosingBrace(string $content, int $openBracePos): int|false
    {
        $braceCount = 1;
        $length = strlen($content);
        $stringQuote = null;
        $escaped = false;

        for ($i = $openBracePos + 1; $i < $length; $i++) {
            $char = $content[$i];

            if ($stringQuote === null) {
                if ($char === '{') {
                    $braceCount++;
                } elseif ($char === '}') {
                    $braceCount--;

                    if ($braceCount === 0) {
                        return $i;
                    }
                } elseif (($char === '"' || $char === "'") && ! $escaped) {
                    $stringQuote = $char;
                }
            } elseif ($char === $stringQuote && ! $escaped) {
                $stringQuote = null;
            }

            $escaped = ($char === '\\' && ! $escaped);
        }

        return false;
    }

    protected function findInsertionPoint(string $content, int $openBracePos, int $closeBracePos): int
    {
        for ($i = $closeBracePos - 1; $i > $openBracePos; $i--) {
            if (! in_array($content[$i], [' ', "\t", "\n", "\r"], true)) {
                return $i + 1;
            }
        }

        return $openBracePos + 1;
    }

    public function detectIndentation(string $content, int $nearPosition): int
    {
        $lines = explode("\n", substr($content, 0, $nearPosition));

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = $lines[$i];

            if (preg_match('/^(\s*)"([^"]+)"\s*:\s*\{/', $line, $matches)) {
                $indent = strlen($matches[1]);

                // The configKey line itself sits one level shallower than its servers
                return $matches[2] === $this->configKey ? max($indent * 2, 4) : $indent;
            }
        }

        return $this->defaultIndentation;
    }

    /**
     * Is the file content plain JSON, without JSON5 features?
     */
    protected function isPlainJson(string $content): bool
    {
        if ($this->hasJson5Features($content)) {
            return false;
        }

        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function hasJson5Features(string $content): bool
    {
        if ($this->hasUnquotedComments($content)) {
            return true;
        }

        if (preg_match('/,\s*[\]}]/', $content)) {
            return true;
        }

        if (preg_match('/^\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:/m', $content)) {
            return true;
        }

        return $this->hasSingleQuotedStrings($content);
    }

    protected function hasUnquotedComments(string $content): bool
    {
        return $this->maskUnquotedComments($content) !== $content;
    }

    protected function hasSingleQuotedStrings(string $content): bool
    {
        $pattern = '/"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'/';

        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $match) {
                if ($match[0] === "'") {
                    return true;
                }
            }
        }

        return false;
    }

    protected function createNewFile(): bool
    {
        $config = $this->baseConfig;
        $this->addServersToConfig($config);

        return $this->writeJsonConfig($config);
    }

    protected function addServersToConfig(array|object &$config): void
    {
        if (is_array($config)) {
            $config = (object) $config;
        }

        if (! isset($config->{$this->configKey}) || ! is_object($config->{$this->configKey})) {
            $config->{$this->configKey} = new stdClass;
        }

        foreach ($this->serversToAdd as $key => $serverConfig) {
            $config->{$this->configKey}->{$key} = $serverConfig;
        }
    }

    protected function writeJsonConfig(object $config): bool
    {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json) {
            $json = str_replace("\r\n", "\n", $json);
        }

        return $json && $this->writeFile($json);
    }

    protected function ensureDirectoryExists(): void
    {
        File::ensureDirectoryExists(dirname($this->filePath));
    }

    protected function fileExists(): bool
    {
        return File::exists($this->filePath);
    }

    protected function readFile(): string
    {
        return File::get($this->filePath);
    }

    protected function normalizeContent(string $content): string
    {
        return trim(Str::chopStart($content, "\xEF\xBB\xBF"));
    }

    protected function writeFile(string $content): bool
    {
        return File::put($this->filePath, Str::finish($content, "\n")) !== false;
    }
}
