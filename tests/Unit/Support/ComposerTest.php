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

/**
 * @param  array<string, mixed>  $data
 */
function writeAppComposerJson(array $data): void
{
    file_put_contents(base_path('composer.json'), json_encode($data));
}

/**
 * @param  array<string, mixed>|string  $data
 */
function writeVendorComposerJson(string $package, array|string $data): void
{
    $directory = base_path('vendor'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $package));

    File::ensureDirectoryExists($directory);

    file_put_contents(
        $directory.DIRECTORY_SEPARATOR.'composer.json',
        is_string($data) ? $data : json_encode($data),
    );
}

it('reads require and require-dev from composer.json', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'require-dev' => [
            'pestphp/pest' => '^3.0',
        ],
    ]);

    $packages = Composer::packages();

    expect($packages)
        ->toHaveKey('laravel/framework')
        ->toHaveKey('pestphp/pest');
});

it('returns package directories that exist in vendor', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
            'nonexistent/pkg' => '^1.0.0',
        ],
    ]);

    $dir = base_path('vendor'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'framework');
    File::ensureDirectoryExists($dir);

    $directories = Composer::packagesDirectories();

    expect($directories)
        ->toHaveKey('laravel/framework')
        ->not->toHaveKey('nonexistent/pkg');
});

it('returns packages directories with boost guidelines', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
            'laravel/horizon' => '^5.0',
        ],
    ]);

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

it('includes packages listed in the extra laravel-boost config', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'packages' => [
                    'acme/first',
                    'acme/second',
                ],
            ],
        ],
    ]);

    $packages = Composer::packages();

    expect($packages)
        ->toHaveKey('laravel/framework')
        ->toHaveKey('acme/first')
        ->toHaveKey('acme/second');
});

it('includes the requirements of packages listed in include-packages-from', function (): void {
    writeAppComposerJson([
        'require' => [
            'acme/bundle' => '^1.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'include-packages-from' => [
                    'acme/bundle',
                ],
            ],
        ],
    ]);

    writeVendorComposerJson('acme/bundle', [
        'require' => [
            'spatie/laravel-permission' => '^6.0',
            'livewire/livewire' => '^3.0',
        ],
    ]);

    $packages = Composer::packages();

    expect($packages)
        ->toHaveKey('acme/bundle')
        ->toHaveKey('spatie/laravel-permission')
        ->toHaveKey('livewire/livewire');
});

it('ignores platform requirements when including packages from another package', function (): void {
    writeAppComposerJson([
        'extra' => [
            'laravel-boost' => [
                'include-packages-from' => [
                    'acme/bundle',
                ],
            ],
        ],
    ]);

    writeVendorComposerJson('acme/bundle', [
        'require' => [
            'php' => '^8.4',
            'ext-json' => '*',
            'spatie/laravel-permission' => '^6.0',
        ],
    ]);

    $packages = Composer::packages();

    expect($packages)
        ->toHaveKey('spatie/laravel-permission')
        ->not->toHaveKey('php')
        ->not->toHaveKey('ext-json');
});

it('ignores include-packages-from entries that are not installed', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'include-packages-from' => [
                    'acme/not-installed',
                ],
            ],
        ],
    ]);

    $packages = Composer::packages();

    expect($packages)
        ->toHaveKey('laravel/framework')
        ->not->toHaveKey('acme/not-installed');
});

it('ignores platform requirements listed in the extra laravel-boost packages', function (): void {
    writeAppComposerJson([
        'extra' => [
            'laravel-boost' => [
                'packages' => [
                    'php',
                    'ext-json',
                    'acme/first',
                ],
            ],
        ],
    ]);

    $packages = Composer::packages();

    expect($packages)
        ->toHaveKey('acme/first')
        ->not->toHaveKey('php')
        ->not->toHaveKey('ext-json');
});

it('keeps the real constraint when a package is also listed in the extra laravel-boost config', function (): void {
    writeAppComposerJson([
        'require' => [
            'spatie/laravel-permission' => '^6.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'packages' => [
                    'spatie/laravel-permission',
                ],
            ],
        ],
    ]);

    expect(Composer::packages())->toMatchArray(['spatie/laravel-permission' => '^6.0']);
});

it('ignores a malformed extra laravel-boost config', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'extra' => [
            'laravel-boost' => 'nope',
        ],
    ]);

    expect(Composer::packages())->toHaveKey('laravel/framework');
});

it('ignores malformed extra laravel-boost package lists', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'packages' => 'acme/first',
                'include-packages-from' => 'acme/bundle',
            ],
        ],
    ]);

    expect(Composer::packages())
        ->toHaveKey('laravel/framework')
        ->not->toHaveKey('acme/first');
});

it('ignores a composer.json that does not decode to an object', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'include-packages-from' => [
                    'acme/scalar',
                ],
            ],
        ],
    ]);

    writeVendorComposerJson('acme/scalar', '"not an object"');

    expect(Composer::packages())
        ->toHaveKey('laravel/framework')
        ->not->toHaveKey('acme/scalar');
});

it('ignores an include-packages-from source with a non-array require', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'include-packages-from' => [
                    'acme/bundle',
                ],
            ],
        ],
    ]);

    writeVendorComposerJson('acme/bundle', ['require' => 'spatie/laravel-permission']);

    expect(Composer::packages())
        ->toHaveKey('laravel/framework')
        ->not->toHaveKey('spatie/laravel-permission');
});

it('exposes the names of packages opted in through the extra laravel-boost config', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'packages' => [
                    'acme/explicit',
                ],
                'include-packages-from' => [
                    'acme/bundle',
                ],
            ],
        ],
    ]);

    writeVendorComposerJson('acme/bundle', [
        'require' => [
            'livewire/livewire' => '^3.0',
        ],
    ]);

    expect(Composer::extraPackageNames())
        ->toContain('acme/explicit')
        ->toContain('livewire/livewire')
        ->not->toContain('laravel/framework');
});

it('ignores include-packages-from entries with a malformed composer.json', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'include-packages-from' => [
                    'acme/broken',
                ],
            ],
        ],
    ]);

    writeVendorComposerJson('acme/broken', '{"require": {');

    $packages = Composer::packages();

    expect($packages)
        ->toHaveKey('laravel/framework')
        ->not->toHaveKey('acme/broken');
});

it('merges packages from every extra laravel-boost source', function (): void {
    writeAppComposerJson([
        'require' => [
            'laravel/framework' => '^11.0',
        ],
        'extra' => [
            'laravel-boost' => [
                'packages' => [
                    'acme/explicit',
                ],
                'include-packages-from' => [
                    'acme/first-bundle',
                    'acme/second-bundle',
                ],
            ],
        ],
    ]);

    writeVendorComposerJson('acme/first-bundle', [
        'require' => [
            'spatie/laravel-permission' => '^6.0',
        ],
    ]);

    writeVendorComposerJson('acme/second-bundle', [
        'require' => [
            'livewire/livewire' => '^3.0',
        ],
    ]);

    $packages = Composer::packages();

    expect($packages)
        ->toHaveKey('laravel/framework')
        ->toHaveKey('acme/explicit')
        ->toHaveKey('spatie/laravel-permission')
        ->toHaveKey('livewire/livewire');
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
