<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Boost\Install\Enums\Platform;

class Herd
{
    public function isInstalled(): bool
    {
        if (! $this->isWindowsPlatform()) {
            return file_exists('/Applications/Herd.app/Contents/MacOS/Herd');
        }

        return is_dir($this->getHomePath().'/.config/herd');
    }

    public function getHomePath(): string
    {
        if ($this->isWindowsPlatform()) {
            if (! isset($_SERVER['HOME'])) {
                $_SERVER['HOME'] = $_SERVER['USERPROFILE'];
            }

            $_SERVER['HOME'] = str_replace('\\', '/', $_SERVER['HOME']);
        }

        return $_SERVER['HOME'];
    }

    public function isWindowsPlatform(): bool
    {
        return Platform::current() === Platform::Windows;
    }
}
