<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class Composer
{
    public static function packagesDirectories(): array
    {
        return collect(static::packages())
            ->mapWithKeys(fn (string $key, string $package): array => [$package => implode(DIRECTORY_SEPARATOR, [
                base_path('vendor'),
                str_replace('/', DIRECTORY_SEPARATOR, $package),
            ])])
            ->filter(fn (string $path): bool => is_dir($path))
            ->toArray();
    }

    public static function packages(): array
    {
        $composerJsonPath = base_path('composer.json');

        if (! file_exists($composerJsonPath)) {
            return [];
        }

        $composerData = json_decode(file_get_contents($composerJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return collect($composerData['require'] ?? [])
            ->merge($composerData['require-dev'] ?? [])
            ->mapWithKeys(fn (string $key, string $package): array => [$package => $key])
            ->toArray();
    }

    public static function packagesDirectoriesWithBoostGuidelines(): array
    {
        return collect(Composer::packagesDirectories())
            ->map(fn (string $path): string => implode(DIRECTORY_SEPARATOR, [
                $path,
                'resources',
                'boost',
                'guidelines',
            ]))->filter(fn (string $path): bool => is_dir($path))
            ->toArray();
    }
}
