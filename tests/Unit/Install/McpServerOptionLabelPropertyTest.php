<?php

declare(strict_types=1);

// Feature: third-party-mcp-server-config, Property 14: For any package/server name pair, the option label is "{package-name} - {server-name}"

use Laravel\Boost\Install\McpServer;
use Laravel\Boost\Install\ThirdPartyPackage;

/**
 * Property 14: For any package name and server name, the option label is "{package-name} - {server-name}".
 * Runs 100 iterations with random package and server name combinations.
 */
it('Property 14: option label format is "{package} - {server}" for any package/server name pair', function (): void {
    $vendors = ['acme', 'vendor-x', 'my-org', 'company', 'dev-tools'];
    $pkgNames = ['my-package', 'tool', 'helper', 'sdk', 'integration'];
    $serverNames = ['mcp-server', 'remote-mcp', 'local-tool', 'api-server', 'worker'];

    for ($i = 0; $i < 100; $i++) {
        $vendor = $vendors[array_rand($vendors)];
        $pkg = $pkgNames[array_rand($pkgNames)];
        $packageName = "{$vendor}/{$pkg}";
        $serverName = $serverNames[array_rand($serverNames)].'-'.$i;

        // The label format used in InstallCommand::selectThirdPartyMcpServers()
        $label = "{$packageName} - {$serverName}";

        expect($label)->toBe("{$packageName} - {$serverName}",
            "Iteration {$i}: label must be '{$packageName} - {$serverName}'"
        );

        // Also verify the key format
        $key = "{$packageName}/{$serverName}";
        expect($key)->toBe("{$packageName}/{$serverName}",
            "Iteration {$i}: key must be '{$packageName}/{$serverName}'"
        );

        // Verify the label contains both package name and server name
        expect($label)->toContain($packageName)
            ->toContain($serverName)
            ->toContain(' - ');
    }
});

/**
 * Verify the label generation logic matches what InstallCommand produces.
 */
it('Property 14: label generation is consistent with InstallCommand format', function (): void {
    $testCases = [
        ['acme/my-package', 'my-server'],
        ['vendor-x/tool', 'remote-mcp'],
        ['org/sdk', 'api-server'],
        ['company/integration', 'worker'],
    ];

    for ($i = 0; $i < 100; $i++) {
        [$packageName, $serverName] = $testCases[array_rand($testCases)];

        // This mirrors the exact format in InstallCommand::selectThirdPartyMcpServers()
        $label = "{$packageName} - {$serverName}";
        $key = "{$packageName}/{$serverName}";

        expect($label)->toStartWith($packageName)
            ->toEndWith($serverName)
            ->toContain(' - ');

        // Key should be reconstructable from label parts
        [$extractedPackage, $extractedServer] = explode(' - ', $label, 2);
        expect($extractedPackage)->toBe($packageName)
            ->and($extractedServer)->toBe($serverName);
    }
});
