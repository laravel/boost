<?php

declare(strict_types=1);

// Feature: third-party-mcp-server-config, Property 1: For any package set, packagesDirectoriesWithBoostMcp() never returns first-party packages
// Feature: third-party-mcp-server-config, Property 2: For any package directory, presence in results iff mcp.json is a regular file

use Illuminate\Support\Facades\File;
use Laravel\Boost\Support\Composer;

afterEach(function (): void {
    if (file_exists(base_path('composer.json'))) {
        unlink(base_path('composer.json'));
    }
    File::deleteDirectory(base_path('vendor'));
});

/**
 * Property 1: For any package set, results never include first-party packages.
 * Runs 100 iterations with random subsets of first-party packages mixed with third-party ones.
 */
it('Property 1: never returns first-party packages regardless of package set', function (): void {
    $firstParty = Composer::FIRST_PARTY_PACKAGES;
    $thirdParty = ['acme/alpha', 'acme/beta', 'vendor-x/tool', 'vendor-y/helper'];

    for ($i = 0; $i < 100; $i++) {
        // Random subset of first-party + random subset of third-party
        $fpSubset = array_slice($firstParty, 0, random_int(0, count($firstParty)));
        $tpSubset = array_slice($thirdParty, 0, random_int(0, count($thirdParty)));
        $allPackages = array_merge($fpSubset, $tpSubset);

        if (empty($allPackages)) {
            continue;
        }

        $require = array_fill_keys($allPackages, '^1.0');
        file_put_contents(base_path('composer.json'), json_encode(['require' => $require]));

        // Give every package a mcp.json
        foreach ($allPackages as $pkg) {
            $dir = base_path(implode(DIRECTORY_SEPARATOR, [
                'vendor', str_replace('/', DIRECTORY_SEPARATOR, $pkg), 'resources', 'boost', 'mcp',
            ]));
            File::ensureDirectoryExists($dir);
            file_put_contents($dir.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));
        }

        $result = Composer::packagesDirectoriesWithBoostMcp();

        foreach ($firstParty as $fp) {
            expect($result)->not->toHaveKey($fp, "Iteration {$i}: first-party package {$fp} must not appear in results");
        }

        // Cleanup for next iteration
        File::deleteDirectory(base_path('vendor'));
        unlink(base_path('composer.json'));
    }
})->skip(fn () => false);

/**
 * Property 2: A package appears in results if and only if its mcp.json is a regular file.
 * Runs 100 iterations with random combinations of packages with/without mcp.json.
 */
it('Property 2: package appears in results iff mcp.json exists as a regular file', function (): void {
    $candidates = ['acme/alpha', 'acme/beta', 'vendor-x/tool', 'vendor-y/helper', 'org/pkg'];

    for ($i = 0; $i < 100; $i++) {
        $withMcp = array_slice($candidates, 0, random_int(0, count($candidates)));
        $withoutMcp = array_diff($candidates, $withMcp);

        $require = array_fill_keys($candidates, '^1.0');
        file_put_contents(base_path('composer.json'), json_encode(['require' => $require]));

        // Create vendor dirs for all
        foreach ($candidates as $pkg) {
            $vendorDir = base_path(implode(DIRECTORY_SEPARATOR, [
                'vendor', str_replace('/', DIRECTORY_SEPARATOR, $pkg),
            ]));
            File::ensureDirectoryExists($vendorDir);
        }

        // Only give mcp.json to $withMcp packages
        foreach ($withMcp as $pkg) {
            $dir = base_path(implode(DIRECTORY_SEPARATOR, [
                'vendor', str_replace('/', DIRECTORY_SEPARATOR, $pkg), 'resources', 'boost', 'mcp',
            ]));
            File::ensureDirectoryExists($dir);
            file_put_contents($dir.DIRECTORY_SEPARATOR.'mcp.json', json_encode(['servers' => []]));
        }

        $result = Composer::packagesDirectoriesWithBoostMcp();

        foreach ($withMcp as $pkg) {
            expect($result)->toHaveKey($pkg, "Iteration {$i}: {$pkg} with mcp.json should be in results");
        }

        foreach ($withoutMcp as $pkg) {
            expect($result)->not->toHaveKey($pkg, "Iteration {$i}: {$pkg} without mcp.json should not be in results");
        }

        // Cleanup for next iteration
        File::deleteDirectory(base_path('vendor'));
        unlink(base_path('composer.json'));
    }
});
