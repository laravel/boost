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
        ->and($package->tokens)->toBe(150);
});

it('defaults tokens to zero', function (): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: true,
        hasSkills: false,
    );

    expect($package->tokens)->toBe(0);
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

it('returns correct display label', function (bool $hasGuidelines, bool $hasSkills, int $tokens, string $expected): void {
    $package = new ThirdPartyPackage(
        name: 'vendor/package',
        hasGuidelines: $hasGuidelines,
        hasSkills: $hasSkills,
        tokens: $tokens,
    );

    expect($package->displayLabel())->toBe($expected);
})->with([
    'both with tokens' => [true, true, 150, 'vendor/package (guidelines, skills) (~150 tokens)'],
    'guidelines with tokens' => [true, false, 200, 'vendor/package (guideline) (~200 tokens)'],
    'skills without tokens' => [false, true, 0, 'vendor/package (skills)'],
    'guidelines zero tokens' => [true, false, 0, 'vendor/package (guideline)'],
]);
