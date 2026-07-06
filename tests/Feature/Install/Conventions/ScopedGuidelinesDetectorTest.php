<?php

declare(strict_types=1);

use Laravel\Boost\Install\Conventions\Detection;
use Laravel\Boost\Install\Conventions\Detectors\ScopedGuidelinesDetector;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\Herd;
use Laravel\Roster\Enums\NodePackageManager;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->roster->shouldReceive('nodePackageManager')->andReturn(NodePackageManager::NPM);
    $this->roster->shouldReceive('usesVersion')->andReturn(false)->byDefault();

    $this->herd = Mockery::mock(Herd::class);
    $this->herd->shouldReceive('isInstalled')->andReturn(false)->byDefault();

    $this->app->instance(Roster::class, $this->roster);
});

it('offers directory-specific composed guidelines as opt-in scoped candidates', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '3.0.0'),
    ]));

    $detections = (new ScopedGuidelinesDetector(new GuidelineComposer($this->roster, $this->herd)))->detect();

    $pest = $detections->firstWhere('id', 'scoped-guideline:pest/core');

    expect($pest)->not->toBeNull();
    expect($pest->glob)->toBe('tests/**');
    expect($pest->guidelineKey)->toBe('pest/core');
    expect($pest->provenance)->toBe(Detection::PROVENANCE_BOOST_GUIDELINE);
    expect($pest->preselected)->toBeFalse();
    expect($pest->note)->toContain('_Boost `pest/core` guideline, scoped to `tests/**`._');

    // Always-on guidelines are never offered for scoping.
    expect($detections->pluck('id')->all())
        ->not->toContain('scoped-guideline:foundation', 'scoped-guideline:boost', 'scoped-guideline:php', 'scoped-guideline:laravel/core');
});

it('offers nothing when no scopable package guidelines are composed', function (): void {
    $this->roster->shouldReceive('packages')->andReturn(new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]));

    $detections = (new ScopedGuidelinesDetector(new GuidelineComposer($this->roster, $this->herd)))->detect();

    expect($detections)->toBeEmpty();
});
