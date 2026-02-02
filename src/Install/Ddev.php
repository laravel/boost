<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

class Ddev
{
    public function isInstalled(): bool
    {
        return file_exists(base_path('.ddev/config.yaml'));
    }

    public function isActive(): bool
    {
        return getenv('IS_DDEV_PROJECT') === 'true';
    }

    /**
     * @return array{key: string, command: string, args: array<int, string>}
     */
    public function buildMcpCommand(string $serverName): array
    {
        return [
            'key' => $serverName,
            'command' => 'ddev',
            'args' => ['exec', 'php', 'artisan', 'boost:mcp'],
        ];
    }

    public static function artisanCommand(): string
    {
        return self::command('php artisan');
    }

    public static function composerCommand(): string
    {
        return self::command('composer');
    }

    public static function binCommand(): string
    {
        return self::command('vendor/bin/');
    }

    public static function command(string $command): string
    {
        return 'ddev exec '.$command;
    }
}
