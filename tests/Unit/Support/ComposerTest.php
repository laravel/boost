<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Support\Composer;

afterEach(function (): void {
    if (file_exists(base_path('composer.json'))) {
        unlink(base_path('composer.json'));
    }

    File::deleteDirectory(base_path('vendor'));
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

it('returns package directories that exist in vendor', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            'laravel/framework' => '^11.0',
            'nonexistent/pkg' => '^1.0.0',
        ],
    ]));

    $dir = base_path('vendor'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'framework');
    File::ensureDirectoryExists($dir);

    $directories = Composer::packagesDirectories();

    expect($directories)
        ->toHaveKey('laravel/framework')
        ->not->toHaveKey('nonexistent/pkg');
});

it('returns packages directories with boost guidelines', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            'laravel/framework' => '^11.0',
            'laravel/horizon' => '^5.0',
        ],
    ]));

    $withGuidelines = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'laravel', 'framework', 'resources', 'boost', 'guidelines',
    ]));
    File::ensureDirectoryExists($withGuidelines);

    $withoutGuidelines = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'laravel', 'horizon',
    ]));
    File::ensureDirectoryExists($withoutGuidelines);

    $result = Composer::packagesDirectoriesWithBoostGuidelines();

    expect($result)
        ->toHaveKey('laravel/framework')
        ->not->toHaveKey('laravel/horizon');
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
