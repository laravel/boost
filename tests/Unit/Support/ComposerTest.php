<?php

declare(strict_types=1);

use Laravel\Boost\Support\Composer;

it('returns composer name for known first-party package', function (): void {
    expect(Composer::composerNameForPackage('pennant'))->toBe('laravel/pennant')
        ->and(Composer::composerNameForPackage('livewire'))->toBe('livewire/livewire')
        ->and(Composer::composerNameForPackage('pest'))->toBe('pestphp/pest');
});

it('returns null for unknown package', function (): void {
    expect(Composer::composerNameForPackage('unknown-package'))->toBeNull()
        ->and(Composer::composerNameForPackage('inertia-react'))->toBeNull()
        ->and(Composer::composerNameForPackage('tailwindcss'))->toBeNull();
});

it('identifies first-party packages', function (): void {
    expect(Composer::isFirstPartyPackage('laravel/framework'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('livewire/livewire'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('pestphp/pest'))->toBeTrue();
});

it('rejects non-first-party packages', function (): void {
    expect(Composer::isFirstPartyPackage('some/package'))->toBeFalse()
        ->and(Composer::isFirstPartyPackage('laravel/unknown'))->toBeFalse();
});
