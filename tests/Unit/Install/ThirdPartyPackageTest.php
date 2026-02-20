<?php

declare(strict_types=1);

use Laravel\Boost\Install\ThirdPartyPackage;

it('creates a package with all properties', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package-name',
        hasGuidelines: true,
        hasSkills: true,
    );

    expect($package->name)->toBe('vendor/package-name')
        ->and($package->hasGuidelines)->toBeTrue()
        ->and($package->hasSkills)->toBeTrue();
});

it('returns correct feature label', function (bool $hasGuidelines, bool $hasSkills, string $expected): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: $hasGuidelines,
        hasSkills: $hasSkills,
    );

    expect($package->featureLabel())->toBe($expected);
})->with([
    'both features' => [true, true, 'guidelines, skills'],
    'guidelines only' => [true, false, 'guideline'],
    'skills only' => [false, true, 'skills'],
    'no features' => [false, false, ''],
]);

it('returns correct display label', function (bool $hasGuidelines, bool $hasSkills, string $expected): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: $hasGuidelines,
        hasSkills: $hasSkills,
    );

    expect($package->displayLabel())->toBe($expected);
})->with([
    'both features' => [true, true, 'vendor/package (guidelines, skills)'],
    'guidelines only' => [true, false, 'vendor/package (guideline)'],
    'skills only' => [false, true, 'vendor/package (skills)'],
]);

it('excludes first-party packages from discover results', function (): void {
    $packages = ThirdPartyPackage::discover();

    $firstPartyNames = [
        'laravel/framework',
        'livewire/livewire',
        'pestphp/pest',
        'phpunit/phpunit',
        'laravel/folio',
        'laravel/mcp',
        'laravel/pennant',
        'laravel/pint',
        'laravel/sail',
        'laravel/wayfinder',
        'livewire/flux',
        'livewire/flux-pro',
        'livewire/volt',
        'inertiajs/inertia-laravel',
    ];

    foreach ($firstPartyNames as $name) {
        expect($packages->has($name))->toBeFalse(
            "First-party package {$name} should be excluded from discover()"
        );
    }
});
