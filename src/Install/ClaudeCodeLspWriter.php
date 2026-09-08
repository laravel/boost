<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Boost\Contracts\SupportsLsp;
use RuntimeException;

class ClaudeCodeLspWriter
{
    public const SUCCESS = 0;

    public function __construct(protected SupportsLsp $agent)
    {
        //
    }

    public function write(): int
    {
        $path = base_path($this->agent->lspConfigPath());
        $manifestDir = $path.DIRECTORY_SEPARATOR.'.claude-plugin';

        if (! is_dir($manifestDir) && ! @mkdir($manifestDir, 0755, true)) {
            throw new RuntimeException("Failed to register the Laravel language server: could not create [{$manifestDir}]");
        }

        $manifest = $this->encode([
            'name' => 'laravel-lsp',
            'description' => 'Registers the Laravel language server for PHP and Blade files.',
        ]);

        $servers = $this->encode([
            'laravel' => [
                'command' => 'laravel-lsp',
                'extensionToLanguage' => [
                    '.php' => 'php',
                    '.blade.php' => 'blade',
                ],
            ],
        ]);

        $written = file_put_contents($manifestDir.DIRECTORY_SEPARATOR.'plugin.json', $manifest) !== false
            && file_put_contents($path.DIRECTORY_SEPARATOR.'.lsp.json', $servers) !== false;

        if (! $written) {
            throw new RuntimeException("Failed to register the Laravel language server: could not write to [{$path}]");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function encode(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
}
