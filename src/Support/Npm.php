<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class Npm
{
    /** @var array<int, string> */
    public const FIRST_PARTY_SCOPES = [
        '@inertiajs',
        '@laravel',
    ];

    /** @var array<int, string> */
    public const FIRST_PARTY_PACKAGES = [
        'laravel-echo',
        'laravel-precognition',
        'laravel-vite-plugin',
    ];

    public static function isFirstPartyPackage(string $npmName): bool
    {
        if (collect(self::FIRST_PARTY_SCOPES)->contains(fn (string $scope): bool => str_starts_with($npmName, $scope.'/'))) {
            return true;
        }

        return in_array($npmName, self::FIRST_PARTY_PACKAGES, true);
    }
}
