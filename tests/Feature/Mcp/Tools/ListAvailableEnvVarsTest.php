<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Mcp\Tools\ListAvailableEnvVars;
use Laravel\Mcp\Request;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

uses(InteractsWithPublishedFiles::class);

beforeEach(function (): void {
    $this->files = ['.env', '.env.test', '.env.example.test'];
});

it('lists env vars from specified file', function (): void {
    File::put(base_path('.env.test'), "APP_NAME=TestApp\nAPP_ENV=testing\nDB_HOST=localhost\nDB_PASSWORD=secret");

    $tool = new ListAvailableEnvVars;
    $response = $tool->handle(new Request(['filename' => '.env.test']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($data): void {
            expect($data)->toBeArray()
                ->toContain('APP_NAME', 'APP_ENV', 'DB_HOST', 'DB_PASSWORD');
        });
});

it('returns sorted env var names', function (): void {
    File::put(base_path('.env.test'), "ZEBRA_VAR=1\nAPPLE_VAR=2\nBANANA_VAR=3");

    $tool = new ListAvailableEnvVars;
    $response = $tool->handle(new Request(['filename' => '.env.test']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($data): void {
            expect($data)->toBe(['APPLE_VAR', 'BANANA_VAR', 'ZEBRA_VAR']);
        });
});

it('ignores commented env vars', function (): void {
    File::put(base_path('.env.test'), "APP_NAME=Test\n# COMMENTED_VAR=value\nAPP_ENV=local\n#ANOTHER_COMMENT=value");

    $tool = new ListAvailableEnvVars;
    $response = $tool->handle(new Request(['filename' => '.env.test']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($data): void {
            expect($data)->toContain('APP_NAME', 'APP_ENV')
                ->not->toContain('COMMENTED_VAR', 'ANOTHER_COMMENT');
        });
});

it('returns error for non-env files', function (): void {
    $tool = new ListAvailableEnvVars;
    $response = $tool->handle(new Request(['filename' => 'config/app.php']));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('can only read .env files');
});

it('returns error when file not found', function (): void {
    $tool = new ListAvailableEnvVars;
    $response = $tool->handle(new Request(['filename' => '.env.nonexistent']));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('File not found');
});

it('returns error for empty env file', function (): void {
    File::put(base_path('.env.test'), '');

    $tool = new ListAvailableEnvVars;
    $response = $tool->handle(new Request(['filename' => '.env.test']));

    expect($response)->isToolResult()
        ->toolHasError()
        ->toolTextContains('Failed to');
});

it('handles env vars with various formats', function (): void {
    File::put(base_path('.env.test'), "SIMPLE=value\nQUOTED=\"quoted value\"\nSINGLE_QUOTED='single quoted'\nEMPTY_VALUE=");

    $tool = new ListAvailableEnvVars;
    $response = $tool->handle(new Request(['filename' => '.env.test']));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($data): void {
            expect($data)->toContain('SIMPLE', 'QUOTED', 'SINGLE_QUOTED', 'EMPTY_VALUE');
        });
});

it('uses default .env when no filename is provided', function (): void {
    File::put(base_path('.env'), "DEFAULT_VAR=from_default\nANOTHER_VAR=value");

    $tool = new ListAvailableEnvVars;
    $response = $tool->handle(new Request([]));

    expect($response)->isToolResult()
        ->toolHasNoError()
        ->toolJsonContent(function ($data): void {
            expect($data)->toContain('DEFAULT_VAR', 'ANOTHER_VAR');
        });
});
