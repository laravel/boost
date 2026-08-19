<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\ThirdPartyPackage;

afterEach(function (): void {
    File::deleteDirectory(base_path('node_modules'));
    File::deleteDirectory(base_path('vendor'));
    @unlink(base_path('package.json'));
    @unlink(base_path('composer.json'));
});

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

it('excludes first-party packages and includes third-party ones from both ecosystems', function (): void {
    foreach ([
        'vendor/laravel/folio',
        'vendor/acme/toolkit',
        'node_modules/@laravel/some-package',
        'node_modules/@acme/ui',
    ] as $package) {
        File::ensureDirectoryExists(base_path($package.'/resources/boost/guidelines'));
    }

    File::ensureDirectoryExists(base_path('vendor/acme/toolkit/resources/boost/skills'));

    file_put_contents(base_path('composer.json'), json_encode([
        'require' => ['laravel/folio' => '^1.0', 'acme/toolkit' => '^1.0'],
    ]));
    file_put_contents(base_path('package.json'), json_encode([
        'dependencies' => ['@laravel/some-package' => '^1.0', '@acme/ui' => '^1.0'],
    ]));

    $packages = ThirdPartyPackage::discover();

    expect($packages)
        ->not->toHaveKey('laravel/folio')
        ->not->toHaveKey('@laravel/some-package')
        ->toHaveKey('acme/toolkit')
        ->toHaveKey('@acme/ui')
        ->and($packages->get('acme/toolkit')->hasSkills)->toBeTrue()
        ->and($packages->get('@acme/ui')->hasGuidelines)->toBeTrue()
        ->and($packages->get('@acme/ui')->hasSkills)->toBeFalse();
});
