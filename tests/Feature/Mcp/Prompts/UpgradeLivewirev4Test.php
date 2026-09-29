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
        ->toolTextContains('Config file updates')
        ->toolTextContains('`wire:model` now ignores child events by default')
        ->toolTextContains('`wire:navigate:scroll`')
        ->toolTextContains('`wire:transition`')
        ->toolTextContains('Islands');
});

test('it uses configured executables for command snippets', function (): void {
    config([
        'boost.executable_paths.php' => '/usr/local/bin/php8.3',
        'boost.executable_paths.composer' => '/usr/local/bin/composer',
    ]);

    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('/usr/local/bin/composer require livewire/livewire:^4.0')
        ->toContain('/usr/local/bin/php8.3 artisan optimize:clear')
        ->toContain('/usr/local/bin/php8.3 artisan make:livewire create-post')
        ->toContain('/usr/local/bin/composer remove livewire/volt')
        ->not->toContain("\ncomposer require livewire/livewire:^4.0\n")
        ->not->toContain("\nphp artisan optimize:clear\n");
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
