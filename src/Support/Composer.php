<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class Composer
{
    /** @var array<int, string> */
    public const FIRST_PARTY_PACKAGES = [
        'laravel/framework',
        'laravel/folio',
        'laravel/mcp',
        'laravel/pennant',
        'laravel/pint',
        'laravel/sail',
        'laravel/wayfinder',
        'livewire/livewire',
        'livewire/flux',
        'livewire/flux-pro',
        'livewire/volt',
        'inertiajs/inertia-laravel',
        'pestphp/pest',
        'phpunit/phpunit',
    ];

    public static function isFirstPartyPackage(string $composerName): bool
    {
        return in_array($composerName, self::FIRST_PARTY_PACKAGES, true);
    }

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
        return self::packagesDirectoriesWithBoostSubpath('guidelines');
    }

    public static function packagesDirectoriesWithBoostSkills(): array
    {
        return self::packagesDirectoriesWithBoostSubpath('skills');
    }

    /**
     * @return array<string, string>
     */
    private static function packagesDirectoriesWithBoostSubpath(string $subpath): array
    {
        return collect(self::packagesDirectories())
            ->map(fn (string $path): string => implode(DIRECTORY_SEPARATOR, array_filter([
                $path,
                'resources',
                'boost',
                $subpath,
            ])))
            ->filter(fn (string $path): bool => is_dir($path))
            ->toArray();
    }
}
