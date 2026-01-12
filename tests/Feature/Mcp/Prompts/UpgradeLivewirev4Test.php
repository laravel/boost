<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Prompts\UpgradeLivewirev4\UpgradeLivewireV4;

beforeEach(function (): void {
    $this->prompt = new UpgradeLivewireV4;
});

test('it has the correct name', function (): void {
    expect($this->prompt->name())->toBe('upgrade-livewire-v4');
});

test('it returns a valid response', function (): void {
    $response = $this->prompt->handle();

    expect($response)
        ->isToolResult()
        ->toolHasNoError();
});

test('it contains core upgrade content', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Livewire v3 to v4 Upgrade Specialist')
        ->toolTextContains('Config File Updates')
        ->toolTextContains('`wire:model` Now Ignores Child Events by Default')
        ->toolTextContains('`wire:navigate:scroll`')
        ->toolTextContains('`wire:transition`')
        ->toolTextContains('Islands');
});

test('it properly compiles blade assist helpers', function (): void {
    $response = $this->prompt->handle();
    $text = (string) $response->content();

    expect($text)
        ->toContain('composer require livewire/livewire')
        ->toContain('php artisan optimize:clear')
        ->not->toContain('$assist->composerCommand')
        ->not->toContain('$assist->artisanCommand')
        ->not->toContain('{{ $assist');
});
