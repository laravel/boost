<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Install\McpServer;
use Laravel\Boost\Install\ThirdPartyPackage;

it('creates a package with all properties', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package-name',
        hasGuidelines: true,
        hasSkills: true,
        hasMcp: true,
    );

    expect($package->name)->toBe('vendor/package-name')
        ->and($package->hasGuidelines)->toBeTrue()
        ->and($package->hasSkills)->toBeTrue()
        ->and($package->hasMcp)->toBeTrue();
});

it('defaults hasMcp to false', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: false,
    );

    expect($package->hasMcp)->toBeFalse();
});

it('returns correct feature label', function (bool $hasGuidelines, bool $hasSkills, bool $hasMcp, string $expected): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: $hasGuidelines,
        hasSkills: $hasSkills,
        hasMcp: $hasMcp,
    );

    expect($package->featureLabel())->toBe($expected);
})->with([
    'all three features'        => [true, true, true, 'guidelines, skills, mcp'],
    'guidelines and skills'     => [true, true, false, 'guidelines, skills'],
    'guidelines and mcp'        => [true, false, true, 'guidelines, mcp'],
    'skills and mcp'            => [false, true, true, 'skills, mcp'],
    'guidelines only'           => [true, false, false, 'guideline'],
    'skills only'               => [false, true, false, 'skills'],
    'mcp only'                  => [false, false, true, 'mcp'],
    'no features'               => [false, false, false, ''],
]);

it('returns correct display label', function (bool $hasGuidelines, bool $hasSkills, bool $hasMcp, string $expected): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: $hasGuidelines,
        hasSkills: $hasSkills,
        hasMcp: $hasMcp,
    );

    expect($package->displayLabel())->toBe($expected);
})->with([
    'all three features'    => [true, true, true, 'vendor/package (guidelines, skills, mcp)'],
    'guidelines and skills' => [true, true, false, 'vendor/package (guidelines, skills)'],
    'guidelines only'       => [true, false, false, 'vendor/package (guideline)'],
    'skills only'           => [false, true, false, 'vendor/package (skills)'],
    'mcp only'              => [false, false, true, 'vendor/package (mcp)'],
]);

it('mcpServers returns empty collection by default', function (): void {
    $package = new ThirdPartyPackage(name: 'vendor/pkg', hasGuidelines: false, hasSkills: false);

    expect($package->mcpServers())->toBeInstanceOf(Collection::class)
        ->and($package->mcpServers())->toBeEmpty();
});

it('warnings returns empty array by default', function (): void {
    $package = new ThirdPartyPackage(name: 'vendor/pkg', hasGuidelines: false, hasSkills: false);

    expect($package->warnings())->toBe([]);
});

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
