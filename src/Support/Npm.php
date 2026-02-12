<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

use Laravel\Roster\Package;

class Npm
{
    /** @var array<int, string> */
    public const FIRST_PARTY_SCOPES = [
        '@inertiajs',
        '@laravel',
    ];

    public static function isFirstPartyPackage(string $npmName): bool
    {
        foreach (self::FIRST_PARTY_SCOPES as $scope) {
            if (str_starts_with($npmName, $scope.'/')) {
                return true;
            }
        }

        return false;
    }

    public static function boostPath(Package $package, string $subpath): ?string
    {
        $path = implode(DIRECTORY_SEPARATOR, [
            base_path('node_modules'),
            str_replace('/', DIRECTORY_SEPARATOR, $package->rawName()),
            'resources',
            'boost',
            $subpath,
        ]);

        return is_dir($path) ? $path : null;
    }

    /**
     * @return array<string, string>
     */
    public static function packagesDirectories(): array
    {
        return collect(static::packages())
            ->mapWithKeys(fn (string $key, string $package): array => [$package => implode(DIRECTORY_SEPARATOR, [
                base_path('node_modules'),
                str_replace('/', DIRECTORY_SEPARATOR, $package),
            ])])
            ->filter(fn (string $path): bool => is_dir($path))
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public static function packages(): array
    {
        $packageJsonPath = base_path('package.json');

        if (! file_exists($packageJsonPath)) {
            return [];
        }

        $packageData = json_decode(file_get_contents($packageJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return collect($packageData['dependencies'] ?? [])
            ->merge($packageData['devDependencies'] ?? [])
            ->mapWithKeys(fn (string $key, string $package): array => [$package => $key])
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public static function packagesDirectoriesWithBoostGuidelines(): array
    {
        return self::packagesDirectoriesWithBoostSubpath('guidelines');
    }

    /**
     * @return array<string, string>
     */
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
