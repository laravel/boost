<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\ThirdPartyPackage;

test('discover returns a collection of ThirdPartyPackage objects', function (): void {
    $packages = ThirdPartyPackage::discover(app(GuidelineComposer::class));

    expect($packages)->toBeInstanceOf(Collection::class);

    foreach ($packages as $package) {
        expect($package)->toBeInstanceOf(ThirdPartyPackage::class);
    }
});

test('discovered packages have at least one feature', function (): void {
    $packages = ThirdPartyPackage::discover(app(GuidelineComposer::class));

    foreach ($packages as $package) {
        expect($package->hasGuidelines || $package->hasSkills)->toBeTrue(
            "Package {$package->name} should have at least guidelines or skills"
        );
    }
});

test('packages with guidelines have a token count greater than zero', function (): void {
    $packages = ThirdPartyPackage::discover(app(GuidelineComposer::class));

    foreach ($packages as $package) {
        if ($package->hasGuidelines) {
            expect($package->tokens)->toBeGreaterThan(0);
        }
    }
});

test('packages are keyed by their name', function (): void {
    $packages = ThirdPartyPackage::discover(app(GuidelineComposer::class));

    foreach ($packages as $key => $package) {
        expect($key)->toBe($package->name);
    }
});

test('feature label returns the correct value for packages with both features', function (): void {
    $package = new ThirdPartyPackage(
        name: 'test/package',
        hasGuidelines: true,
        hasSkills: true,
    );

    expect($package->featureLabel())->toBe('guidelines, skills');
});

test('the display label includes all relevant information', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: true,
        tokens: 100,
    );

    $label = $package->displayLabel();

    expect($label)->toContain('vendor/package')
        ->and($label)->toContain('(guidelines, skills)')
        ->and($label)->toContain('(~100 tokens)');
});

test('the display label excludes tokens for skill-only packages', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: false,
        hasSkills: true,
        tokens: 0,
    );

    $label = $package->displayLabel();

    expect($label)->toContain('vendor/package')
        ->and($label)->toContain('(skills)')
        ->and($label)->not->toContain('tokens');
});
