<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use const DIRECTORY_SEPARATOR;

class Sail
{
    public const SAIL_BINARY_PATH = 'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'sail';

    public function isInstalled(): bool
    {
        return file_exists(base_path(self::SAIL_BINARY_PATH)) &&
               (file_exists(base_path('docker-compose.yml')) || file_exists(base_path('compose.yaml')));
    }

    public function isActive(): bool
    {
        return get_current_user() === 'sail' || getenv('LARAVEL_SAIL') === '1';
    }
}
