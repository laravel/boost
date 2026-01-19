<?php

declare(strict_types=1);

use Laravel\Boost\Support\Config;

beforeEach(function (): void {
    // Create a boost.json to simulate existing installation
    $config = new Config;
    $config->setGuidelines(['test-guideline']);
});

afterEach(function (): void {
    (new Config)->flush();
});

test('install command skips existing installation check with --no-interaction flag', function (): void {
    // With --no-interaction, the existing installation check is bypassed
    // The command will proceed to completion
    $this->artisan('boost:install', ['--no-interaction' => true])
        ->assertSuccessful();
});

test('install command skips existing installation check with --force flag', function (): void {
    // With --force, the existing installation check is bypassed
    // The command will proceed to completion
    $this->artisan('boost:install', ['--force' => true, '--no-interaction' => true])
        ->assertSuccessful();
});

test('install command proceeds when no existing installation exists', function (): void {
    // Flush the config to simulate fresh install
    (new Config)->flush();

    // Should proceed without warning
    $this->artisan('boost:install', ['--no-interaction' => true])
        ->assertSuccessful();
});
