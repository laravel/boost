<?php

declare(strict_types=1);

// Feature: third-party-mcp-server-config, Property 3: For any array without a non-empty name, McpServer::fromArray() throws InvalidArgumentException
// Feature: third-party-mcp-server-config, Property 4: For any McpServer, toConfigArray() contains no null or empty values
// Feature: third-party-mcp-server-config, Property 5: For any valid mcp.json, parse → encode → parse produces equivalent McpServer objects

use Laravel\Boost\Install\McpServer;

/**
 * Property 3: For any array without a non-empty name, fromArray() throws.
 * Runs 100 iterations with various invalid name inputs.
 */
it('Property 3: fromArray() throws for any array without a non-empty name', function (): void {
    $invalidNames = [
        null,
        '',
        '   ',
        0,
        false,
        [],
        123,
        1.5,
    ];

    for ($i = 0; $i < 100; $i++) {
        $invalidName = $invalidNames[array_rand($invalidNames)];

        $data = ['command' => 'node', 'args' => ['server.js']];

        if ($invalidName === null) {
            // No name key at all
        } else {
            $data['name'] = $invalidName;
        }

        expect(fn () => McpServer::fromArray($data))
            ->toThrow(InvalidArgumentException::class, message: "Iteration {$i}: fromArray() should throw for name=".json_encode($invalidName));
    }
});

/**
 * Property 4: For any McpServer, toConfigArray() contains no null or empty values.
 * Runs 100 iterations with randomly populated McpServer instances.
 */
it('Property 4: toConfigArray() never contains null or empty values', function (): void {
    $possibleNames = ['server-a', 'my-mcp', 'acme-tool', 'remote-server'];
    $possibleCommands = [null, 'node', 'php', 'python'];
    $possibleUrls = [null, 'https://mcp.example.com', 'https://api.acme.com/mcp'];
    $possibleTypes = [null, 'http', 'stdio'];
    $possibleDescriptions = [null, '', 'My server', 'A useful tool'];

    for ($i = 0; $i < 100; $i++) {
        $server = new McpServer(
            name: $possibleNames[array_rand($possibleNames)],
            command: $possibleCommands[array_rand($possibleCommands)],
            args: random_int(0, 1) ? ['arg1', 'arg2'] : [],
            url: $possibleUrls[array_rand($possibleUrls)],
            type: $possibleTypes[array_rand($possibleTypes)],
            env: random_int(0, 1) ? ['KEY' => 'val'] : [],
            description: $possibleDescriptions[array_rand($possibleDescriptions)],
        );

        $config = $server->toConfigArray();

        foreach ($config as $key => $value) {
            expect($value)->not->toBeNull("Iteration {$i}: key '{$key}' should not be null");
            expect($value)->not->toBe('', "Iteration {$i}: key '{$key}' should not be empty string");
            if (is_array($value)) {
                expect($value)->not->toBeEmpty("Iteration {$i}: key '{$key}' should not be empty array");
            }
        }
    }
});

/**
 * Property 5: For any valid mcp.json content, parse → encode → parse produces equivalent McpServer objects.
 */
it('Property 5: mcp.json round-trip parse produces equivalent McpServer objects', function (): void {
    $serverTemplates = [
        ['name' => 'cmd-server', 'command' => 'node', 'args' => ['server.js'], 'env' => ['KEY' => 'val']],
        ['name' => 'http-server', 'type' => 'http', 'url' => 'https://mcp.example.com/mcp'],
        ['name' => 'minimal-server', 'command' => 'php'],
        ['name' => 'full-server', 'command' => 'node', 'args' => ['a', 'b'], 'description' => 'Full server', 'env' => ['A' => '1']],
    ];

    for ($i = 0; $i < 100; $i++) {
        // Pick a random subset of server templates
        $count = random_int(1, count($serverTemplates));
        $selected = array_slice($serverTemplates, 0, $count);

        $mcpJson = json_encode(['servers' => $selected]);

        // First parse
        $data = json_decode($mcpJson, true);
        $originalServers = array_map(fn (array $entry) => McpServer::fromArray($entry), $data['servers']);

        // Re-encode via toConfigArray
        $reEncoded = json_encode(['servers' => array_map(fn (McpServer $s) => $s->toConfigArray(), $originalServers)]);

        // Second parse
        $data2 = json_decode($reEncoded, true);
        $roundTrippedServers = array_map(fn (array $entry) => McpServer::fromArray($entry), $data2['servers']);

        expect(count($roundTrippedServers))->toBe(count($originalServers), "Iteration {$i}: server count should match after round-trip");

        foreach ($originalServers as $idx => $original) {
            $roundTripped = $roundTrippedServers[$idx];
            expect($roundTripped->name)->toBe($original->name, "Iteration {$i}: name should match");
            expect($roundTripped->command)->toBe($original->command, "Iteration {$i}: command should match");
            expect($roundTripped->url)->toBe($original->url, "Iteration {$i}: url should match");
            expect($roundTripped->type)->toBe($original->type, "Iteration {$i}: type should match");
        }
    }
});
