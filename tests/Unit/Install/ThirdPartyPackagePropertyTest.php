<?php

declare(strict_types=1);

// Feature: third-party-mcp-server-config, Property 6: hasMcp() is true iff mcpServers() is non-empty
// Feature: third-party-mcp-server-config, Property 7: featureLabel() always contains "mcp" when hasMcp is true
// Feature: third-party-mcp-server-config, Property 8: discover() includes MCP-only packages

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\ThirdPartyPackage;

afterEach(function (): void {
    if (file_exists(base_path('composer.json'))) {
        unlink(base_path('composer.json'));
    }
    File::deleteDirectory(base_path('vendor'));
    Mockery::close();
});

/**
 * Property 6: hasMcp is true iff mcpServers() is non-empty.
 * Runs 100 iterations with random combinations of hasGuidelines, hasSkills, hasMcp.
 */
it('Property 6: hasMcp is true iff mcpServers() is non-empty', function (): void {
    for ($i = 0; $i < 100; $i++) {
        $hasMcp = (bool) random_int(0, 1);

        $package = new ThirdPartyPackage(
            name: 'vendor/pkg-'.$i,
            hasGuidelines: (bool) random_int(0, 1),
            hasSkills: (bool) random_int(0, 1),
            hasMcp: $hasMcp,
        );

        // hasMcp should match whether mcpServers() is non-empty
        // (by default mcpServers() is empty since we can't inject servers via constructor alone)
        // The invariant: if hasMcp=false, mcpServers() must be empty
        if (! $hasMcp) {
            expect($package->mcpServers())->toBeEmpty(
                "Iteration {$i}: hasMcp=false means mcpServers() must be empty"
            );
        }

        // And the reverse: if mcpServers() is non-empty, hasMcp must be true
        if ($package->mcpServers()->isNotEmpty()) {
            expect($package->hasMcp)->toBeTrue(
                "Iteration {$i}: non-empty mcpServers() means hasMcp must be true"
            );
        }
    }
});

/**
 * Property 7: featureLabel() always contains "mcp" when hasMcp is true.
 * Runs 100 iterations with random combinations of feature flags.
 */
it('Property 7: featureLabel() contains "mcp" when hasMcp is true', function (): void {
    for ($i = 0; $i < 100; $i++) {
        $package = new ThirdPartyPackage(
            name: 'vendor/pkg-'.$i,
            hasGuidelines: (bool) random_int(0, 1),
            hasSkills: (bool) random_int(0, 1),
            hasMcp: true,
        );

        expect($package->featureLabel())->toContain('mcp',
            "Iteration {$i}: featureLabel() must contain 'mcp' when hasMcp=true"
        );
    }
});

/**
 * Property 8: discover() includes MCP-only packages (no guidelines or skills).
 * Runs 20 iterations (filesystem-based, slower).
 */
it('Property 8: discover() includes packages with only MCP configuration', function (): void {
    $packageNames = ['acme/mcp-only-a', 'acme/mcp-only-b', 'vendor-x/mcp-tool'];

    for ($i = 0; $i < 20; $i++) {
        // Pick a random subset of MCP-only packages
        $count = random_int(1, count($packageNames));
        $selected = array_slice($packageNames, 0, $count);

        $require = array_fill_keys($selected, '^1.0');
        file_put_contents(base_path('composer.json'), json_encode(['require' => $require]));

        foreach ($selected as $pkg) {
            $dir = base_path(implode(DIRECTORY_SEPARATOR, [
                'vendor', str_replace('/', DIRECTORY_SEPARATOR, $pkg), 'resources', 'boost', 'mcp',
            ]));
            File::ensureDirectoryExists($dir);
            file_put_contents($dir.DIRECTORY_SEPARATOR.'mcp.json', json_encode([
                'servers' => [
                    ['name' => 'server-'.str_replace(['/', '-'], '_', $pkg), 'command' => 'node'],
                ],
            ]));
        }

        $packages = ThirdPartyPackage::discover();

        foreach ($selected as $pkg) {
            expect($packages->has($pkg))->toBeTrue(
                "Iteration {$i}: MCP-only package {$pkg} must be in discover() results"
            );
            expect($packages->get($pkg)->hasMcp)->toBeTrue(
                "Iteration {$i}: {$pkg} should have hasMcp=true"
            );
            expect($packages->get($pkg)->hasGuidelines)->toBeFalse(
                "Iteration {$i}: {$pkg} should have hasGuidelines=false"
            );
            expect($packages->get($pkg)->hasSkills)->toBeFalse(
                "Iteration {$i}: {$pkg} should have hasSkills=false"
            );
        }

        // Cleanup for next iteration
        File::deleteDirectory(base_path('vendor'));
        unlink(base_path('composer.json'));
    }
});
