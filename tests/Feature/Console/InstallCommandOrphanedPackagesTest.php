<?php

declare(strict_types=1);

use Laravel\Boost\Install\ThirdPartyPackage;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

use function Laravel\Prompts\multiselect;

beforeEach(function (): void {
    (new Config)->flush();
});

it('passes only valid defaults to multiselect when orphaned packages exist in config', function (): void {
    Prompt::fake([Key::ENTER]);

    $configuredPackages = ['valid-pkg', 'orphaned-pkg'];

    $discoveredPackages = collect([
        'valid-pkg' => new ThirdPartyPackage('valid-pkg', true, false),
    ]);

    $validDefaults = collect($configuredPackages)
        ->filter(fn (string $name) => $discoveredPackages->has($name))
        ->values()
        ->toArray();

    $result = multiselect(
        label: 'Select packages',
        options: $discoveredPackages->mapWithKeys(fn (ThirdPartyPackage $pkg, string $name): array => [
            $name => $pkg->displayLabel(),
        ])->toArray(),
        default: $validDefaults,
    );

    expect($result)->toContain('valid-pkg')
        ->and($result)->not->toContain('orphaned-pkg');
})->skipOnWindows();

it('does not show mcp-only packages in guidelines skills package list', function (): void {
    $discoveredPackages = collect([
        'guidelines-pkg' => new ThirdPartyPackage('guidelines-pkg', true, false, false),
        'skills-pkg' => new ThirdPartyPackage('skills-pkg', false, true, false),
        'mixed-pkg' => new ThirdPartyPackage('mixed-pkg', true, true, true),
        'mcp-only-pkg' => new ThirdPartyPackage('mcp-only-pkg', false, false, true),
    ]);

    $options = $discoveredPackages
        ->filter(fn (ThirdPartyPackage $pkg): bool => $pkg->hasGuidelines || $pkg->hasSkills)
        ->mapWithKeys(fn (ThirdPartyPackage $pkg, string $name): array => [
            $name => $pkg->displayLabel(),
        ])->toArray();

    expect(array_keys($options))
        ->toContain('guidelines-pkg', 'skills-pkg', 'mixed-pkg')
        ->not->toContain('mcp-only-pkg');
})->skipOnWindows();
