<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Boost\Install\ThirdPartyPackage;

test('discover returns packages with valid structure', function (): void {
    $packages = ThirdPartyPackage::discover();

    expect($packages)->toBeInstanceOf(Collection::class);

    foreach ($packages as $key => $package) {
        expect($package)->toBeInstanceOf(ThirdPartyPackage::class)
            ->and($key)->toBe($package->name)
            ->and($package->hasGuidelines || $package->hasSkills)->toBeTrue(
                "Package {$package->name} should have at least guidelines or skills"
            );
    }
});
