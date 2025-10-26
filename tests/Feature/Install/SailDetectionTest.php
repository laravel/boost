<?php

declare(strict_types=1);

namespace Tests\Feature\Install;

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\CodeEnvironment\ClaudeCode;
use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

beforeEach(function (): void {
    $this->strategyFactory = app(DetectionStrategyFactory::class);
});

test('ClaudeCode detects Sail when both sail and docker-compose exist', function (): void {
    $sailPath = base_path('vendor/bin/sail');
    $dockerPath = base_path('docker-compose.yml');

    // Create Sail files
    File::ensureDirectoryExists(dirname($sailPath));
    File::put($sailPath, '#!/usr/bin/env bash');
    File::put($dockerPath, 'version: "3"');

    $claudeCode = new ClaudeCode($this->strategyFactory);

    expect($claudeCode->getPhpPath())->toBe('./vendor/bin/sail')
        ->and($claudeCode->getArtisanPath())->toBe('artisan');

    // Cleanup
    File::delete($sailPath);
    File::delete($dockerPath);
});

test('ClaudeCode uses php when Sail is not detected', function (): void {
    $sailPath = base_path('vendor/bin/sail');
    $dockerPath = base_path('docker-compose.yml');

    // Ensure Sail files don't exist
    if (File::exists($sailPath)) {
        File::delete($sailPath);
    }
    if (File::exists($dockerPath)) {
        File::delete($dockerPath);
    }

    $claudeCode = new ClaudeCode($this->strategyFactory);

    expect($claudeCode->getPhpPath())->toBe('php')
        ->and($claudeCode->getArtisanPath())->toBe('artisan');
});

test('ClaudeCode uses php when only sail exists but no docker-compose', function (): void {
    $sailPath = base_path('vendor/bin/sail');
    $dockerPath = base_path('docker-compose.yml');

    // Create only Sail file
    File::ensureDirectoryExists(dirname($sailPath));
    File::put($sailPath, '#!/usr/bin/env bash');

    // Ensure docker-compose doesn't exist
    if (File::exists($dockerPath)) {
        File::delete($dockerPath);
    }

    $claudeCode = new ClaudeCode($this->strategyFactory);

    expect($claudeCode->getPhpPath())->toBe('php')
        ->and($claudeCode->getArtisanPath())->toBe('artisan');

    // Cleanup
    File::delete($sailPath);
});

test('ClaudeCode uses php when only docker-compose exists but no sail', function (): void {
    $sailPath = base_path('vendor/bin/sail');
    $dockerPath = base_path('docker-compose.yml');

    // Create only docker-compose file
    File::put($dockerPath, 'version: "3"');

    // Ensure sail doesn't exist
    if (File::exists($sailPath)) {
        File::delete($sailPath);
    }

    $claudeCode = new ClaudeCode($this->strategyFactory);

    expect($claudeCode->getPhpPath())->toBe('php')
        ->and($claudeCode->getArtisanPath())->toBe('artisan');

    // Cleanup
    File::delete($dockerPath);
});

test('PhpStorm detects Sail and uses bash with docker exec command', function (): void {
    $sailPath = base_path('vendor/bin/sail');
    $dockerPath = base_path('docker-compose.yml');

    // Create Sail files
    File::ensureDirectoryExists(dirname($sailPath));
    File::put($sailPath, '#!/usr/bin/env bash');
    File::put($dockerPath, 'version: "3"');

    $phpStorm = new PhpStorm($this->strategyFactory);

    expect($phpStorm->getPhpPath())->toBe('bash')
        ->and($phpStorm->getArtisanPath())->toBe('-lc');

    // Cleanup
    File::delete($sailPath);
    File::delete($dockerPath);
});

test('PhpStorm uses absolute paths when Sail is not detected', function (): void {
    $sailPath = base_path('vendor/bin/sail');
    $dockerPath = base_path('docker-compose.yml');
    $composePath = base_path('compose.yaml');

    // Ensure Sail files don't exist
    if (File::exists($sailPath)) {
        File::delete($sailPath);
    }
    if (File::exists($dockerPath)) {
        File::delete($dockerPath);
    }
    if (File::exists($composePath)) {
        File::delete($composePath);
    }

    $phpStorm = new PhpStorm($this->strategyFactory);

    expect($phpStorm->getPhpPath())->toBe(PHP_BINARY)
        ->and($phpStorm->getArtisanPath())->toBe(base_path('artisan'));
});

test('ClaudeCode detects Sail with compose.yaml (current Docker Compose format)', function (): void {
    $sailPath = base_path('vendor/bin/sail');
    $composePath = base_path('compose.yaml');

    // Create Sail files with new compose.yaml format
    File::ensureDirectoryExists(dirname($sailPath));
    File::put($sailPath, '#!/usr/bin/env bash');
    File::put($composePath, 'services:');

    $claudeCode = new ClaudeCode($this->strategyFactory);

    expect($claudeCode->getPhpPath())->toBe('./vendor/bin/sail')
        ->and($claudeCode->getArtisanPath())->toBe('artisan');

    // Cleanup
    File::delete($sailPath);
    File::delete($composePath);
});

test('PhpStorm detects Sail with compose.yaml and uses bash with docker exec', function (): void {
    $sailPath = base_path('vendor/bin/sail');
    $composePath = base_path('compose.yaml');

    // Create Sail files with new compose.yaml format
    File::ensureDirectoryExists(dirname($sailPath));
    File::put($sailPath, '#!/usr/bin/env bash');
    File::put($composePath, 'services:');

    $phpStorm = new PhpStorm($this->strategyFactory);

    expect($phpStorm->getPhpPath())->toBe('bash')
        ->and($phpStorm->getArtisanPath())->toBe('-lc');

    // Cleanup
    File::delete($sailPath);
    File::delete($composePath);
});
