<?php

declare(strict_types=1);

use Laravel\Boost\Support\Npm;

it('identifies scoped first party packages', function (): void {
    expect(Npm::isFirstPartyPackage('@laravel/vite-plugin-wayfinder'))->toBeTrue()
        ->and(Npm::isFirstPartyPackage('@inertiajs/react'))->toBeTrue();
});

it('identifies non-scoped first party packages', function (): void {
    expect(Npm::isFirstPartyPackage('laravel-echo'))->toBeTrue();
});

it('does not identify unknown packages as first party', function (): void {
    expect(Npm::isFirstPartyPackage('axios'))->toBeFalse()
        ->and(Npm::isFirstPartyPackage('lodash'))->toBeFalse()
        ->and(Npm::isFirstPartyPackage('unknown-package'))->toBeFalse()
        ->and(Npm::isFirstPartyPackage('@other/package'))->toBeFalse();
});
