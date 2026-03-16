<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use const DIRECTORY_SEPARATOR;

class Sail
{
    public const DEFAULT_BINARY_PATH = 'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'sail';

    public static function artisanCommand(): string
    {
        return self::command('artisan');
    }

    public static function binCommand(): string
    {
        return self::command('bin ');
    }

    public static function composerCommand(): string
    {
        return self::command('composer');
    }

    public static function nodePackageManagerCommand(string $manager): string
    {
        return self::command($manager);
    }

    public static function command(string $command): string
    {
        return self::binaryPath().' '.$command;
    }

    public static function binaryPath(): string
    {
        return config('boost.executable_paths.sail') ?? self::DEFAULT_BINARY_PATH;
    }

    public function isInstalled(): bool
    {
        $binaryPath = self::binaryPath();
        $resolvedPath = str_starts_with($binaryPath, DIRECTORY_SEPARATOR) ? $binaryPath : base_path($binaryPath);

        return file_exists($resolvedPath) &&
            (file_exists(base_path('docker-compose.yml')) || file_exists(base_path('compose.yaml')));
    }

    public function isActive(): bool
    {
        if ($this->isRunningInDevcontainer()) {
            return false;
        }

        return get_current_user() === 'sail' || getenv('LARAVEL_SAIL') === '1';
    }

    public function isRunningInDevcontainer(): bool
    {
        return getenv('REMOTE_CONTAINERS') === 'true';
    }

    /**
     * @return array{key: string, command: string, args: array<int, string>}
     */
    public function buildMcpCommand(string $serverName): array
    {
        return [
            'key' => $serverName,
            'command' => self::binaryPath(),
            'args' => ['artisan', 'boost:mcp'],
        ];
    }
}
