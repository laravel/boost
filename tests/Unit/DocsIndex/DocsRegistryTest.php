<?php

use Laravel\Boost\DocsIndex\DocsRegistry;

it('returns expected repo structure', function (): void {
    $repos = DocsRegistry::repos();

    expect($repos)->toHaveKeys([
        'laravel/docs',
        'filamentphp/filament',
        'livewire/livewire',
        'pestphp/pest',
    ]);

    expect($repos['laravel/docs'])->toMatchArray([
        'repo' => 'laravel/docs',
        'branch' => '{major}.x',
        'version_from' => 'laravel/framework',
        'path' => '/',
    ]);
});

it('filters repos by installed packages', function (): void {
    $installed = [
        'laravel/framework' => '12.0.0',
        'pestphp/pest' => '4.1.0',
    ];

    $filtered = DocsRegistry::installedRepos($installed);

    expect($filtered)->toHaveKeys(['laravel/docs', 'pestphp/pest'])
        ->not->toHaveKeys(['filamentphp/filament', 'livewire/livewire']);
});

it('returns empty when no packages are installed', function (): void {
    $filtered = DocsRegistry::installedRepos([]);

    expect($filtered)->toBeEmpty();
});

it('returns expected symlink structure', function (): void {
    $symlinks = DocsRegistry::symlinks();

    expect($symlinks)->toHaveKey('filament-blueprint')
        ->and($symlinks['filament-blueprint'])->toMatchArray([
            'package' => 'filament/blueprint',
            'source' => 'vendor/filament/blueprint/resources/markdown/planning',
        ]);
});
