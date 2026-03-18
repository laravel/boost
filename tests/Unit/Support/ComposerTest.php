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

it('returns packages that have a valid mcp.json file', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            'acme/my-package' => '^1.0',
        ],
    ]));

    $mcpJsonPath = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'acme', 'my-package', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($mcpJsonPath);
    file_put_contents($mcpJsonPath.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));

    $result = Composer::packagesDirectoriesWithBoostMcp();

    expect($result)
        ->toHaveKey('acme/my-package')
        ->and($result['acme/my-package'])->toEndWith(implode(DIRECTORY_SEPARATOR, [
            'resources', 'boost', 'mcp', 'mcp.json',
        ]));
});

it('excludes packages that do not have a mcp.json file', function (): void {
    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            'acme/with-mcp' => '^1.0',
            'acme/without-mcp' => '^1.0',
        ],
    ]));

    // Package with mcp.json
    $mcpJsonPath = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'acme', 'with-mcp', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($mcpJsonPath);
    file_put_contents($mcpJsonPath.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));

    // Package without mcp.json (directory exists but no file)
    $noMcpDir = base_path(implode(DIRECTORY_SEPARATOR, ['vendor', 'acme', 'without-mcp']));
    File::ensureDirectoryExists($noMcpDir);

    $result = Composer::packagesDirectoriesWithBoostMcp();

    expect($result)
        ->toHaveKey('acme/with-mcp')
        ->not->toHaveKey('acme/without-mcp');
});

it('excludes first-party packages even if they have a mcp.json file', function (): void {
    $firstPartyPackage = Composer::FIRST_PARTY_PACKAGES[0];

    file_put_contents(base_path('composer.json'), json_encode([
        'require' => [
            $firstPartyPackage => '^1.0',
            'acme/third-party' => '^1.0',
        ],
    ]));

    // Give the first-party package a mcp.json
    $firstPartyVendorPath = str_replace('/', DIRECTORY_SEPARATOR, $firstPartyPackage);
    $firstPartyMcpPath = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', $firstPartyVendorPath, 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($firstPartyMcpPath);
    file_put_contents($firstPartyMcpPath.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));

    // Give the third-party package a mcp.json too
    $thirdPartyMcpPath = base_path(implode(DIRECTORY_SEPARATOR, [
        'vendor', 'acme', 'third-party', 'resources', 'boost', 'mcp',
    ]));
    File::ensureDirectoryExists($thirdPartyMcpPath);
    file_put_contents($thirdPartyMcpPath.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));

    $result = Composer::packagesDirectoriesWithBoostMcp();

    expect($result)
        ->not->toHaveKey($firstPartyPackage)
        ->toHaveKey('acme/third-party');
});

it('excludes all first-party packages from mcp discovery', function (): void {
    $require = [];
    foreach (Composer::FIRST_PARTY_PACKAGES as $package) {
        $require[$package] = '^1.0';
    }
    $require['acme/third-party'] = '^1.0';

    file_put_contents(base_path('composer.json'), json_encode(['require' => $require]));

    // Give every first-party package a mcp.json
    foreach (Composer::FIRST_PARTY_PACKAGES as $package) {
        $vendorPath = str_replace('/', DIRECTORY_SEPARATOR, $package);
        $mcpPath = base_path(implode(DIRECTORY_SEPARATOR, [
            'vendor', $vendorPath, 'resources', 'boost', 'mcp',
        ]));
        File::ensureDirectoryExists($mcpPath);
        file_put_contents($mcpPath.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));
    }

    // Third-party package with mcp.json
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

    expect($result)->toHaveKey('vendor-name/package-name')
        ->and($result['vendor-name/package-name'])->toBe($expectedPath);
});
