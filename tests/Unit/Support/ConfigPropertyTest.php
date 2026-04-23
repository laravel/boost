<?php

declare(strict_types=1);

// Feature: third-party-mcp-server-config, Property 12: setMcpServers / getMcpServers round-trip
// Feature: third-party-mcp-server-config, Property 13: setMcpServers does not affect other config keys

use Laravel\Boost\Support\Config;

afterEach(function (): void {
    (new Config)->flush();
});

/**
 * Property 12: For any array of MCP server key strings, setMcpServers then getMcpServers returns equivalent array.
 * Runs 100 iterations with random server key arrays.
 */
it('Property 12: setMcpServers / getMcpServers round-trip', function (): void {
    $vendors = ['acme', 'vendor-x', 'org', 'company'];
    $packages = ['my-package', 'tool', 'helper', 'sdk'];
    $serverNames = ['mcp-server', 'remote-mcp', 'local-tool', 'api-server'];

    for ($i = 0; $i < 100; $i++) {
        $config = new Config;
        $config->flush();

        // Generate a random array of server keys
        $count = random_int(0, 5);
        $keys = [];
        for ($j = 0; $j < $count; $j++) {
            $vendor = $vendors[array_rand($vendors)];
            $pkg = $packages[array_rand($packages)];
            $server = $serverNames[array_rand($serverNames)];
            $keys[] = "{$vendor}/{$pkg}/{$server}-{$j}";
        }

        $config->setMcpServers($keys);

        $retrieved = $config->getMcpServers();

        expect($retrieved)->toEqual($keys, "Iteration {$i}: round-trip should return equivalent array");
    }
});

/**
 * Property 13: setMcpServers does not affect other config keys.
 * Runs 100 iterations with random pre-existing config state.
 */
it('Property 13: setMcpServers does not affect other config keys', function (): void {
    $agentOptions = [['cursor'], ['copilot'], ['cursor', 'copilot'], []];
    $packageOptions = [['vendor/pkg'], ['acme/tool'], [], ['a/b', 'c/d']];

    for ($i = 0; $i < 100; $i++) {
        $config = new Config;
        $config->flush();

        // Set random initial state
        $mcp = (bool) random_int(0, 1);
        $sail = (bool) random_int(0, 1);
        $nightwatch = (bool) random_int(0, 1);
        $guidelines = (bool) random_int(0, 1);
        $agents = $agentOptions[array_rand($agentOptions)];
        $packages = $packageOptions[array_rand($packageOptions)];

        $config->setMcp($mcp);
        $config->setSail($sail);
        $config->setNightwatchMcp($nightwatch);
        $config->setGuidelines($guidelines);
        if ($agents !== []) {
            $config->setAgents($agents);
        }
        if ($packages !== []) {
            $config->setPackages($packages);
        }

        // Now set mcp_servers
        $config->setMcpServers(['acme/pkg/server-'.$i]);

        // Verify other keys are unaffected
        expect($config->getMcp())->toBe($mcp, "Iteration {$i}: mcp should be unchanged")
            ->and($config->getSail())->toBe($sail, "Iteration {$i}: sail should be unchanged")
            ->and($config->getNightwatchMcp())->toBe($nightwatch, "Iteration {$i}: nightwatch_mcp should be unchanged")
            ->and($config->getGuidelines())->toBe($guidelines, "Iteration {$i}: guidelines should be unchanged");

        if ($agents !== []) {
            expect($config->getAgents())->toEqual($agents, "Iteration {$i}: agents should be unchanged");
        }
        if ($packages !== []) {
            expect($config->getPackages())->toEqual($packages, "Iteration {$i}: packages should be unchanged");
        }
    }
});
