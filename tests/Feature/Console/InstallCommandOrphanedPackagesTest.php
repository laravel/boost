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
