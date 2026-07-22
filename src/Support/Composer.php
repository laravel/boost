<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class Composer
{
    /** @var array<int, string> */
    public const FIRST_PARTY_SCOPES = [
        'laravel',
    ];

    /** @var array<int, string> */
    public const FIRST_PARTY_PACKAGES = [
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
        if (collect(self::FIRST_PARTY_SCOPES)->contains(fn (string $scope): bool => str_starts_with($composerName, $scope.'/'))) {
            return true;
        }

        return in_array($composerName, self::FIRST_PARTY_PACKAGES, true);
    }

    public static function packagesDirectories(): array
    {
        return collect(static::packages())
            ->mapWithKeys(fn (string $key, string $package): array => [$package => self::vendorPath($package)])
            ->filter(fn (string $path): bool => is_dir($path))
            ->toArray();
    }

    public static function packages(): array
    {
        $composerData = self::readComposerJson(base_path('composer.json'));

        return collect(self::extraPackages($composerData))
            ->merge(self::requiresOf($composerData, 'require'))
            ->merge(self::requiresOf($composerData, 'require-dev'))
            ->mapWithKeys(fn (string $key, string $package): array => [$package => $key])
            ->toArray();
    }

    /**
     * Packages opted in through `extra.laravel-boost` in the application's composer.json.
     *
     * @return array<int, string>
     */
    public static function extraPackageNames(): array
    {
        $composerData = self::readComposerJson(base_path('composer.json'));
        $extraPackages = self::extraPackages($composerData);

        return array_keys($extraPackages);
    }

    /**
     * @param  array<string, mixed>  $composerData
     * @return array<string, string>
     */
    private static function extraPackages(array $composerData): array
    {
        $config = $composerData['extra']['laravel-boost'] ?? [];

        if (! is_array($config)) {
            return [];
        }

        $packages = collect(self::stringList($config, 'packages'))
            ->reject(fn (string $package): bool => self::isPlatformRequirement($package))
            ->mapWithKeys(fn (string $package): array => [$package => '*']);

        foreach (self::stringList($config, 'include-packages-from') as $sourcePackage) {
            $packages = $packages->merge(
                self::requiresOf(self::readComposerJson(self::vendorPath($sourcePackage).DIRECTORY_SEPARATOR.'composer.json'), 'require')
            );
        }

        return $packages->all();
    }

    /**
     * @param  array<string, mixed>  $composerData
     * @return array<string, string>
     */
    private static function requiresOf(array $composerData, string $key): array
    {
        $requires = $composerData[$key] ?? [];

        if (! is_array($requires)) {
            return [];
        }

        return collect($requires)
            ->reject(fn (mixed $constraint, string $name): bool => self::isPlatformRequirement($name))
            ->filter(fn (mixed $constraint): bool => is_string($constraint))
            ->all();
    }

    private static function isPlatformRequirement(string $name): bool
    {
        return ! str_contains($name, '/');
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    private static function stringList(array $config, string $key): array
    {
        $values = $config[$key] ?? [];

        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, 'is_string'));
    }

    private static function vendorPath(string $package): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            base_path('vendor'),
            str_replace('/', DIRECTORY_SEPARATOR, $package),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function readComposerJson(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $composerData = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return is_array($composerData) ? $composerData : [];
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
