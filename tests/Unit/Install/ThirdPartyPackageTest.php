<?php

declare(strict_types=1);

use Laravel\Boost\Install\ThirdPartyPackage;

it('creates a package with all properties', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package-name',
        hasGuidelines: true,
        hasSkills: true,
        tokens: 150,
    );

    expect($package->name)->toBe('vendor/package-name')
        ->and($package->hasGuidelines)->toBeTrue()
        ->and($package->hasSkills)->toBeTrue()
        ->and($package->description)->toBe('Package description')
        ->and($package->tokens)->toBe(150);
});

it('returns feature label for guidelines and skills', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: true,
    );

    expect($package->featureLabel())->toBe('guidelines, skills');
});

it('returns feature label for guidelines only', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: false,
    );

    expect($package->featureLabel())->toBe('guideline');
});

it('returns feature label for skills only', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: false,
        hasSkills: true,
    );

    expect($package->featureLabel())->toBe('skills');
});

it('returns empty feature label when no features', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: false,
        hasSkills: false,
    );

    expect($package->featureLabel())->toBe('');
});

it('displays label with guidelines and skills including tokens', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: true,
        tokens: 150,
    );

    expect($package->displayLabel())->toBe('vendor/package (guidelines, skills) (~150 tokens) Auth tools');
});

it('displays label with guidelines only including tokens', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: false,
        tokens: 200,
    );

    expect($package->displayLabel())->toBe('vendor/package (guideline) (~200 tokens) API helpers');
});

it('displays label with skills only without tokens', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: false,
        hasSkills: true,
        tokens: 0,
    );

    expect($package->displayLabel())->toBe('vendor/package (skills) Code generation');
});

it('it displays a label without description when empty', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: false,
        tokens: 100,
    );

    expect($package->displayLabel())->toBe('vendor/package (guideline) (~100 tokens)');
});

it('displays label without tokens when zero', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: false,
        tokens: 0,
    );

    expect($package->displayLabel())->toBe('vendor/package (guideline) Some description');
});

it('defaults optional properties correctly', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: false,
    );

    expect($package->description)->toBe('')
        ->and($package->tokens)->toBe(0);
});
