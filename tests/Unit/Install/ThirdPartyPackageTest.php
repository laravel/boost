<?php

declare(strict_types=1);

use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\ProjectManager;

beforeEach(function (): void {
    $this->project = mock(ProjectManager::class);
});

afterEach(function (): void {
    clearStagedPackages();
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

it('discovers third-party packages from both ecosystems and excludes first-party ones', function (): void {
    mockProjectPackages($this->project, new PackageCollection([
        stagedPackage('acme/toolkit', 'guidelines', 'skills'),
        stagedPackage('laravel/folio', 'guidelines'),
        stagedPackage('@acme/ui', 'guidelines'),
        stagedPackage('@laravel/some-package', 'guidelines'),
    ]));

    $packages = ThirdPartyPackage::discover($this->project);

    expect($packages)
        ->not->toHaveKey('laravel/folio')
        ->not->toHaveKey('@laravel/some-package')
        ->and($packages->get('acme/toolkit')->hasGuidelines)->toBeTrue()
        ->and($packages->get('acme/toolkit')->hasSkills)->toBeTrue()
        ->and($packages->get('@acme/ui')->hasGuidelines)->toBeTrue()
        ->and($packages->get('@acme/ui')->hasSkills)->toBeFalse();
});

it('ignores packages without a resources/boost directory', function (): void {
    mockProjectPackages($this->project, new PackageCollection([
        stagedPackage('acme/plain'),
    ]));

    expect(ThirdPartyPackage::discover($this->project))->toBeEmpty();
});

it('ignores packages that are not installed on disk', function (): void {
    mockProjectPackages($this->project, new PackageCollection([
        rosterPackage('acme/missing', '1.0.0', path: base_path('staged-packages/nope'))->setDirect(),
    ]));

    expect(ThirdPartyPackage::discover($this->project))->toBeEmpty();
});

it('ignores transitive dependencies so an indirect package cannot inject guidelines', function (): void {
    mockProjectPackages($this->project, new PackageCollection([
        rosterPackage('acme/transitive', '1.0.0', path: stagedPackage('acme/transitive', 'guidelines')->path()),
    ]));

    expect(ThirdPartyPackage::discover($this->project))->toBeEmpty();
});
