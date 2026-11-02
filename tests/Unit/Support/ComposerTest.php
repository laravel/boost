<?php

declare(strict_types=1);

use Laravel\Boost\Support\Composer;

afterEach(function (): void {
    if (file_exists(base_path('composer.json'))) {
        unlink(base_path('composer.json'));
    }
});

it('reads require and require-dev from composer.json', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'require-dev' => [
            'pestphp/pest' => '^3.0',
        ],
    ]));

    $packages = Composer::packages();

    expect($packages)
        ->toHaveKey('laravel/framework')
        ->toHaveKey('pestphp/pest');
});

it('identifies scoped first party packages', function (): void {
    expect(Composer::isFirstPartyPackage('laravel/framework'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('laravel/fortify'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('laravel/horizon'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('laravel/anything'))->toBeTrue();
});

it('identifies non-scoped first party packages', function (): void {
    expect(Composer::isFirstPartyPackage('livewire/livewire'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('pestphp/pest'))->toBeTrue()
        ->and(Composer::isFirstPartyPackage('inertiajs/inertia-laravel'))->toBeTrue();
});

it('does not identify unknown packages as first party', function (): void {
    expect(Composer::isFirstPartyPackage('spatie/laravel-permission'))->toBeFalse()
        ->and(Composer::isFirstPartyPackage('doctrine/dbal'))->toBeFalse()
        ->and(Composer::isFirstPartyPackage('unknown/package'))->toBeFalse();
});
