<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use const DIRECTORY_SEPARATOR;

class Sail
{
    public const BINARY_PATH = 'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'sail';

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
        return self::BINARY_PATH.' '.$command;
    }

    public function isInstalled(): bool
    {
        return file_exists(base_path(self::BINARY_PATH)) &&
            (file_exists(base_path('docker-compose.yml')) || file_exists(base_path('compose.yaml')));
    }

    public function isActive(): bool
    {
        return get_current_user() === 'sail' || getenv('LARAVEL_SAIL') === '1';
    }

    /**
     * @return array<int, string>
     */
    public function buildMcpCommand(string $serverName): array
    {
        return [$serverName, self::BINARY_PATH, 'artisan', 'boost:mcp'];
    }
}
