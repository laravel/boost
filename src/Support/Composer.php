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

    public static function packages(): array
    {
        $composerData = self::composerJson();

        return collect($composerData['require'] ?? [])
            ->merge($composerData['require-dev'] ?? [])
            ->mapWithKeys(fn (string $key, string $package): array => [$package => $key])
            ->toArray();
    }

    public static function hasScript(string $script): bool
    {
        return array_key_exists($script, self::composerJson()['scripts'] ?? []);
    }

    protected static function composerJson(): array
    {
        $composerJsonPath = base_path('composer.json');

        if (! file_exists($composerJsonPath)) {
            return [];
        }

        $composerData = json_decode(file_get_contents($composerJsonPath), true);

        return is_array($composerData) ? $composerData : [];
    }
}
