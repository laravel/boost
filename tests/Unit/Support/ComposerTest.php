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

it('maps package name to the absolute path of its mcp.json file', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            'vendor-name/package-name' => '^1.0',
        ],
    ]));

    $mcpDir = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'vendor-name', 'package-name', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($mcpDir);
    file_put_contents($mcpDir.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));

    $result = Composer::packagesDirectoriesWithBoostMcp();

    $expectedPath = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'vendor-name', 'package-name', 'resources', 'boost', 'mcp', 'mcp.json',
    ]));

    expect($result)
        ->toHaveKey('vendor-name/package-name')
        ->and($result['vendor-name/package-name'])->toBe($expectedPath);
});

it('excludes packages that do not have a mcp.json file', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            'acme/with-mcp' => '^1.0',
            'acme/without-mcp' => '^1.0',
        ],
    ]));

    $mcpJsonPath = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'acme', 'with-mcp', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($mcpJsonPath);
    file_put_contents($mcpJsonPath.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));

    $noMcpDir = base_path(implode(DIRECTORY_SEPARATOR, ['vendor', 'acme', 'without-mcp']));
    File::ensureDirectoryExists($noMcpDir);

    $result = Composer::packagesDirectoriesWithBoostMcp();

    expect($result)
        ->toHaveKey('acme/with-mcp')
        ->not->toHaveKey('acme/without-mcp');
});

it('excludes all first-party packages from mcp discovery', function (): void {
    $require = [];
    foreach (Composer::FIRST_PARTY_PACKAGES as $package) {
        $require[$package] = '^1.0';
    }
    $require['acme/third-party'] = '^1.0';

    file_put_contents(base_path('composer.json'), json_encode(['require' => $require]));

    foreach (Composer::FIRST_PARTY_PACKAGES as $package) {
        $vendorPath = str_replace('/', DIRECTORY_SEPARATOR, $package);
        $mcpPath = base_path(implode(DIRECTORY_SEPARATOR, [
            'vendor', $vendorPath, 'resources', 'boost', 'mcp',
        ]));
        File::ensureDirectoryExists($mcpPath);
        file_put_contents($mcpPath.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));
    }

    $thirdPartyMcpPath = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'acme', 'third-party', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($thirdPartyMcpPath);
    file_put_contents($thirdPartyMcpPath.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));

    $result = Composer::packagesDirectoriesWithBoostMcp();

    foreach (Composer::FIRST_PARTY_PACKAGES as $package) {
        expect($result)->not->toHaveKey($package, "First-party package {$package} should be excluded");
    }

    expect($result)->toHaveKey('acme/third-party');
});

it('returns an empty array when no packages have a mcp.json file', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            'acme/no-mcp' => '^1.0',
        ],
    ]));

    $vendorDir = base_path(implode(DIRECTORY_SEPARATOR, ['vendor', 'acme', 'no-mcp']));
    File::ensureDirectoryExists($vendorDir);

    $result = Composer::packagesDirectoriesWithBoostMcp();

    expect($result)->toBe([]);
});

it('returns an empty array when composer.json does not exist', function (): void {
    $result = Composer::packagesDirectoriesWithBoostMcp();

    expect($result)->toBe([]);
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
