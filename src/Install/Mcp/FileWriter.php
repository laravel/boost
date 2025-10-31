<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Mcp;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FileWriter
{
    protected string $configKey = 'mcpServers';

    protected array $serversToAdd = [];

    protected int $defaultIndentation = 8;

    public function __construct(protected string $filePath, protected array $baseConfig = []) {}

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

        if ($this->shouldWriteNew()) {
            return $this->createNewFile();
        }

        $content = $this->readFile();

        if ($this->isPlainJson($content)) {
            return $this->updatePlainJsonFile($content);
        }

        return $this->updateJson5File($content);
    }

    protected function updatePlainJsonFile(string $content): bool
    {
        $config = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        $this->addServersToConfig($config);

        return $this->writeJsonConfig($config);
    }

    protected function updateJson5File(string $content): bool
    {
        $configKeyPattern = '/["\']'.preg_quote($this->configKey, '/').'["\']\\s*:\\s*\\{/';

        if (preg_match($configKeyPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $this->injectIntoExistingConfigKey($content, $matches);
        }

        return $this->injectNewConfigKey($content);
    }

    protected function injectIntoExistingConfigKey(string $content, array $matches): bool
    {
        // $matches[0][1] contains the position of the configKey pattern match
        $configKeyStart = $matches[0][1];

        // Find the opening brace of the configKey object
        $openBracePos = strpos($content, '{', $configKeyStart);
        if ($openBracePos === false) {
            return false;
        }

        // Find the matching closing brace for this configKey object
        $closeBracePos = $this->findMatchingClosingBrace($content, $openBracePos);
        if ($closeBracePos === false) {
            return false;
        }

        // Filter out servers that already exist
        $serversToAdd = $this->filterExistingServers($content, $openBracePos, $closeBracePos);

        if ($serversToAdd === []) {
            return true;
        }

        // Detect indentation from surrounding content
        $indentLength = $this->detectIndentation($content, $closeBracePos);

        $serverJsonParts = [];
        foreach ($serversToAdd as $key => $serverConfig) {
            $serverJsonParts[] = $this->generateServerJson($key, $serverConfig, $indentLength);
        }

        $serversJson = implode(','."\n", $serverJsonParts);

        // Check if we need a comma and add it to the preceding content
        $needsComma = $this->needsCommaBeforeClosingBrace($content, $openBracePos, $closeBracePos);
        if (! $needsComma) {
            $newContent = substr_replace($content, $serversJson, $closeBracePos, 0);

            return $this->writeFile($newContent);
        }

        // Find the position to add comma (after the last meaningful character)
        $commaPosition = $this->findCommaInsertionPoint($content, $openBracePos, $closeBracePos);
        if ($commaPosition !== -1) {
            $newContent = substr_replace($content, ',', $commaPosition, 0);
            $newContent = substr_replace($newContent, $serversJson, $commaPosition + 1, 0);
        } else {
            $newContent = substr_replace($content, $serversJson, $closeBracePos, 0);
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

    protected function injectNewConfigKey(string $content): bool
    {
        $openBracePos = strpos($content, '{');
        if ($openBracePos === false) {
            return false;
        }

        $serverJsonParts = [];
        foreach ($this->serversToAdd as $key => $serverConfig) {
            $serverJsonParts[] = $this->generateServerJson($key, $serverConfig);
        }

        $serversJson = implode(',', $serverJsonParts);
        $configKeySection = '"'.$this->configKey.'": {'.$serversJson.'}';

        $needsComma = $this->needsCommaAfterBrace($content, $openBracePos);
        $injection = $configKeySection.($needsComma ? ',' : '');

        $newContent = substr_replace($content, $injection, $openBracePos + 1, 0);

        return $this->writeFile($newContent);
    }

    protected function generateServerJson(string $key, array $serverConfig, int $baseIndent = 0): string
    {
        $json = json_encode($serverConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Normalize line endings to Unix style
        $json = str_replace("\r\n", "\n", $json);

        // If no indentation needed, return as-is
        if (empty($baseIndent)) {
            return '"'.$key.'": '.$json;
        }

        // Apply indentation to each line of the JSON
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
        $afterBrace = substr($content, $bracePosition + 1);
        $trimmed = preg_replace('/^\s*(?:\/\/.*$)?/m', '', $afterBrace);

        return filled($trimmed) && ! Str::startsWith($trimmed, '}');
    }

    protected function findMatchingClosingBrace(string $content, int $openBracePos): int|false
    {
        $braceCount = 1;
        $length = strlen($content);
        $inString = false;
        $escaped = false;

        for ($i = $openBracePos + 1; $i < $length; $i++) {
            $char = $content[$i];

            if (! $inString) {
                if ($char === '{') {
                    $braceCount++;
                } elseif ($char === '}') {
                    $braceCount--;
                    if ($braceCount === 0) {
                        return $i;
                    }
                }
            }

            // Handle string detection (similar to hasUnquotedComments logic)
            if ($char === '"' && ! $escaped) {
                $inString = ! $inString;
            }

            $escaped = ($char === '\\' && ! $escaped);
        }

        return false;
    }

    protected function needsCommaBeforeClosingBrace(string $content, int $openBracePos, int $closeBracePos): bool
    {
        // Get content between opening and closing braces
        $innerContent = substr($content, $openBracePos + 1, $closeBracePos - $openBracePos - 1);

        // Skip whitespace and comments to find last meaningful character
        $trimmed = preg_replace('/\s+|\/\/.*$/m', '', $innerContent);

        // If empty or ends with opening brace, no comma needed
        if (blank($trimmed) || Str::endsWith($trimmed, '{')) {
            return false;
        }

        // If ends with comma, no additional comma needed
        return ! Str::endsWith($trimmed, ',');
    }

    protected function findCommaInsertionPoint(string $content, int $openBracePos, int $closeBracePos): int
    {
        // Work backwards from closing brace to find last meaningful character
        for ($i = $closeBracePos - 1; $i > $openBracePos; $i--) {
            $char = $content[$i];

            // Skip whitespace and newlines
            if (in_array($char, [' ', "\t", "\n", "\r"], true)) {
                continue;
            }

            // Skip comments (simple approach - if we hit //, skip to start of line)
            if ($i > 0 && $content[$i - 1] === '/' && $char === '/') {
                // Find start of this line
                $lineStart = strrpos($content, "\n", $i - strlen($content)) ?: 0;
                $i = $lineStart;

                continue;
            }

            // Found last meaningful character, comma goes after it
            if ($char !== ',') {
                return $i + 1;
            }

            // Already has comma, no insertion needed
            return -1;
        }

        // Fallback - insert right after opening brace
        return $openBracePos + 1;
    }

    public function detectIndentation(string $content, int $nearPosition): int
    {
        // Look backwards from the position to find server-level indentation
        // We want to find lines that look like: "server-name": {

        $lines = explode("\n", substr($content, 0, $nearPosition));

        // Look for the most recent server definition to match its indentation
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = $lines[$i];
            // Match server definitions: any amount of whitespace + "key": {
            if (preg_match('/^(\s*)"[^"]+"\s*:\s*\{/', $line, $matches)) {
                return strlen($matches[1]);
            }
        }

        // Fallback: assume 8 spaces (2 levels of 4-space indentation typical for JSON)
        return $this->defaultIndentation;
    }

    /**
     * Is the file content plain JSON, without JSON5 features?
     */
    protected function isPlainJson(string $content): bool
    {
        if ($this->hasUnquotedComments($content)) {
            return false;
        }

        // Trailing commas (,] or ,}) - supported in JSON 5
        if (preg_match('/,\s*[\]}]/', $content)) {
            return false;
        }

        // Unquoted keys - supported in JSON 5
        if (preg_match('/^\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:/m', $content)) {
            return false;
        }

        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function hasUnquotedComments(string $content): bool
    {
        // Regex that matches both quoted strings and comments
        // Group 1: Double-quoted strings with escaped characters
        // Group 2: Line comments starting with //
        $pattern = '/"(\\\\.|[^"\\\\])*"|(\/\/.*)/';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                // If group 2 is set, we found a // comment outside strings
                if (! empty($match[2])) {
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

    protected function addServersToConfig(array &$config): void
    {
        foreach ($this->serversToAdd as $key => $serverConfig) {
            data_set($config, $this->configKey.'.'.$key, $serverConfig);
        }
    }

    protected function writeJsonConfig(array $config): bool
    {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Normalize line endings to Unix style
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

    protected function shouldWriteNew(): bool
    {
        if (! $this->fileExists()) {
            return true;
        }

        return File::size($this->filePath) < 3;
        // To account for files that are just `{}`
    }

    protected function readFile(): string
    {
        return File::get($this->filePath);
    }

    protected function writeFile(string $content): bool
    {
        return File::put($this->filePath, $content) !== false;
    }
}
