<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Prompts\UpgradeLivewirev4\UpgradeLivewireV4;

beforeEach(function (): void {
    $this->prompt = new UpgradeLivewireV4;
});

test('it has the correct name', function (): void {
    expect($this->prompt->name())->toBe('upgrade-livewire-v4');
});

test('it has a description', function (): void {
    expect($this->prompt->description())
        ->toContain('Livewire')
        ->toContain('v3 to v4')
        ->toContain('upgrade');
});

test('it returns a valid response', function (): void {
    $response = $this->prompt->handle();

    expect($response)
        ->isToolResult()
        ->toolHasNoError();
});
