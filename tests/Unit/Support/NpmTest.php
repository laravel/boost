<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Support\Npm;

afterEach(function (): void {
    if (file_exists(base_path('package.json'))) {
        unlink(base_path('package.json'));
    }

    File::deleteDirectory(base_path('node_modules'));
});

it('returns empty packages when package.json does not exist', function (): void {
    expect(Npm::packages())->toBe([]);
});

it('returns empty packages when package.json is invalid json', function (): void {
    file_put_contents(base_path('package.json'), 'invalid json {{{');

    expect(Npm::packages())->toBe([]);
});

it('reads dependencies and devDependencies from package.json', function (): void {
    file_put_contents(base_path('package.json'), json_encode([
        'dependencies' => [
            '@inertiajs/vue3' => '^2.0.0',
        ],
        'devDependencies' => [
            'vite' => '^5.0.0',
        ],
    ]));

    $packages = Npm::packages();

    expect($packages)
        ->toHaveKey('@inertiajs/vue3')
        ->toHaveKey('vite');
});

it('returns empty packages directories when node_modules does not exist', function (): void {
    file_put_contents(base_path('package.json'), json_encode([
        'dependencies' => [
            '@inertiajs/vue3' => '^2.0.0',
        ],
    ]));

    expect(Npm::packagesDirectories())->toBe([]);
});

it('returns package directories that exist in node_modules', function (): void {
    file_put_contents(base_path('package.json'), json_encode([
        'dependencies' => [
            '@inertiajs/vue3' => '^2.0.0',
            'nonexistent-pkg' => '^1.0.0',
        ],
    ]));

    $dir = base_path('node_modules'.DIRECTORY_SEPARATOR.'@inertiajs'.DIRECTORY_SEPARATOR.'vue3');
    File::ensureDirectoryExists($dir);

    $directories = Npm::packagesDirectories();

    expect($directories)
        ->toHaveKey('@inertiajs/vue3')
        ->not->toHaveKey('nonexistent-pkg');
});

it('returns packages directories with boost guidelines', function (): void {
    file_put_contents(base_path('package.json'), json_encode([
        'dependencies' => [
            '@inertiajs/vue3' => '^2.0.0',
            '@inertiajs/react' => '^2.0.0',
        ],
    ]));

    $withGuidelines = base_path(implode(DIRECTORY_SEPARATOR, [
        'node_modules', '@inertiajs', 'vue3', 'resources', 'boost', 'guidelines',
    ]));
    File::ensureDirectoryExists($withGuidelines);

    $withoutGuidelines = base_path(implode(DIRECTORY_SEPARATOR, [
        'node_modules', '@inertiajs', 'react',
    ]));
    File::ensureDirectoryExists($withoutGuidelines);

    $result = Npm::packagesDirectoriesWithBoostGuidelines();

    expect($result)
        ->toHaveKey('@inertiajs/vue3')
        ->not->toHaveKey('@inertiajs/react');
});

it('returns packages directories with boost skills', function (): void {
    file_put_contents(base_path('package.json'), json_encode([
        'dependencies' => [
            '@inertiajs/vue3' => '^2.0.0',
        ],
    ]));

    $withSkills = base_path(implode(DIRECTORY_SEPARATOR, [
        'node_modules', '@inertiajs', 'vue3', 'resources', 'boost', 'skills',
    ]));
    File::ensureDirectoryExists($withSkills);

    $result = Npm::packagesDirectoriesWithBoostSkills();

    expect($result)->toHaveKey('@inertiajs/vue3');
});

it('handles package.json with no dependencies', function (): void {
    file_put_contents(base_path('package.json'), json_encode([
        'name' => 'test-app',
    ]));

    expect(Npm::packages())->toBe([]);
});

it('returns non-scoped package directories with boost guidelines', function (): void {
    file_put_contents(base_path('package.json'), json_encode([
        'dependencies' => [
            'laravel-echo' => '^2.0.0',
            'axios' => '^1.0.0',
        ],
    ]));

    $withGuidelines = base_path(implode(DIRECTORY_SEPARATOR, [
        'node_modules', 'laravel-echo', 'resources', 'boost', 'guidelines',
    ]));
    File::ensureDirectoryExists($withGuidelines);

    $withoutGuidelines = base_path(implode(DIRECTORY_SEPARATOR, [
        'node_modules', 'axios',
    ]));
    File::ensureDirectoryExists($withoutGuidelines);

    $result = Npm::packagesDirectoriesWithBoostGuidelines();

    expect($result)
        ->toHaveKey('laravel-echo')
        ->not->toHaveKey('axios');
});

it('returns non-scoped package directories with boost skills', function (): void {
    file_put_contents(base_path('package.json'), json_encode([
        'dependencies' => [
            'laravel-echo' => '^2.0.0',
        ],
    ]));

    $withSkills = base_path(implode(DIRECTORY_SEPARATOR, [
        'node_modules', 'laravel-echo', 'resources', 'boost', 'skills',
    ]));
    File::ensureDirectoryExists($withSkills);

    $result = Npm::packagesDirectoriesWithBoostSkills();

    expect($result)->toHaveKey('laravel-echo');
});

it('identifies non-scoped first party packages', function (): void {
    expect(Npm::isFirstPartyPackage('laravel-echo'))->toBeTrue();
});

it('does not identify unknown packages as first party', function (): void {
    expect(Npm::isFirstPartyPackage('axios'))->toBeFalse()
        ->and(Npm::isFirstPartyPackage('lodash'))->toBeFalse()
        ->and(Npm::isFirstPartyPackage('unknown-package'))->toBeFalse();
});
