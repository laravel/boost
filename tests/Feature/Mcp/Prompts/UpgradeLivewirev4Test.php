<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Prompts\UpgradeLivewirev4\UpgradeLivewireV4;

beforeEach(function (): void {
    $this->prompt = new UpgradeLivewireV4;
});

test('it has correct name', function (): void {
    expect($this->prompt->name())->toBe('upgrade-livewire-v4');
});

test('it has a description', function (): void {
    expect($this->prompt->description())
        ->toContain('Livewire')
        ->toContain('v3 to v4')
        ->toContain('upgrad'); // Matches both "upgrade" and "upgrading"
});

test('it returns a valid response', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolHasNoError();
});

test('it contains upgrade specialist persona', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Livewire v3 to v4 Upgrade Specialist')
        ->toolTextContains('expert Livewire upgrade specialist');
});

test('it contains systematic upgrade process', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Upgrade Process')
        ->toolTextContains('Assess Current State')
        ->toolTextContains('Create Safety Net')
        ->toolTextContains('Analyze Codebase for Breaking Changes')
        ->toolTextContains('Apply Changes Systematically')
        ->toolTextContains('Update Dependencies')
        ->toolTextContains('Test and Verify');
});

test('it contains search patterns for breaking changes', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('High Priority Searches')
        ->toolTextContains('Medium Priority Searches')
        ->toolTextContains('Low Priority Searches')
        ->toolTextContains('config/livewire.php')
        ->toolTextContains('Route::get')
        ->toolTextContains('wire:model')
        ->toolTextContains('wire:scroll');
});

test('it contains execution strategy', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Execution Strategy')
        ->toolTextContains('Batch similar changes')
        ->toolTextContains('parallel agents')
        ->toolTextContains('Prioritize high-impact changes');
});

test('it contains upgrade reference guide', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Livewire v4 Upgrade Reference Guide')
        ->toolTextContains('High-Impact Changes')
        ->toolTextContains('Medium-Impact Changes')
        ->toolTextContains('Low-Impact Changes');
});

test('it contains config file updates section', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Config File Updates')
        ->toolTextContains('component_layout')
        ->toolTextContains('component_placeholder')
        ->toolTextContains('smart_wire_keys');
});

test('it contains routing changes section', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Routing Changes')
        ->toolTextContains('Route::livewire');
});

test('it contains new features section', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('New Features in v4')
        ->toolTextContains('Single-file and multi-file components')
        ->toolTextContains('Islands')
        ->toolTextContains('Deferred loading')
        ->toolTextContains('Async Actions')
        ->toolTextContains('wire:sort')
        ->toolTextContains('wire:intersect')
        ->toolTextContains('wire:ref');
});

test('it contains javascript deprecations section', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('JavaScript Deprecations')
        ->toolTextContains('$wire.$js()')
        ->toolTextContains('commit') // Just check for commit word (it appears in "`commit` and `request` Hooks")
        ->toolTextContains('request') // Just check for request word
        ->toolTextContains('interceptMessage')
        ->toolTextContains('interceptRequest');
});

test('it contains volt upgrade section', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Upgrading Volt')
        ->toolTextContains('Update Component Imports')
        ->toolTextContains('Remove Volt Service Provider')
        ->toolTextContains('Remove Volt Package');
});
