<?php

declare(strict_types=1);

use Laravel\Boost\Support\Config;

beforeEach(function (): void {
    $config = new Config;
    $config->setGuidelines(['test-guideline']);
});

afterEach(function (): void {
    (new Config)->flush();
});

test('install command skips existing installation check with --no-interaction flag', function (): void {
    $this->artisan('boost:install', ['--no-interaction' => true, '--ignore-mcp' => true])
        ->assertSuccessful();
})->skipOnWindows();

test('install command skips existing installation check with --force flag', function (): void {
    $this->artisan('boost:install', ['--force' => true, '--no-interaction' => true, '--ignore-mcp' => true])
        ->assertSuccessful();
})->skipOnWindows();

test('install command proceeds when no existing installation exists', function (): void {
    (new Config)->flush();

    $this->artisan('boost:install', ['--no-interaction' => true, '--ignore-mcp' => true])
        ->assertSuccessful();
})->skipOnWindows();
