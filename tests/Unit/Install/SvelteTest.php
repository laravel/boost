<?php

declare(strict_types=1);

use Laravel\Boost\Install\Svelte;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->svelte = new Svelte($this->roster);
});

test('isInstalled returns true when inertia svelte is detected', function (): void {
    $this->roster->shouldReceive('uses')
        ->with(Packages::INERTIA_SVELTE)
        ->once()
        ->andReturn(true);

    expect($this->svelte->isInstalled())->toBeTrue();
});

test('isInstalled returns false when inertia svelte is not detected', function (): void {
    $this->roster->shouldReceive('uses')
        ->with(Packages::INERTIA_SVELTE)
        ->once()
        ->andReturn(false);

    expect($this->svelte->isInstalled())->toBeFalse();
});

test('mcpUrl returns the svelte mcp url', function (): void {
    expect($this->svelte->mcpUrl())->toBe('https://mcp.svelte.dev/mcp');
});

test('MCP_URL constant matches mcpUrl return value', function (): void {
    expect(Svelte::MCP_URL)->toBe('https://mcp.svelte.dev/mcp');
});
