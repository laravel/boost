<?php

declare(strict_types=1);

use Laravel\Boost\Install\Herd;

$herdTestCleanupData = [];

beforeEach(function (): void {
    global $herdTestCleanupData;

    $herdTestCleanupData = initializeHerdTestEnvironment();

    mkdir($herdTestCleanupData['tempDir'], 0755, true);
});

afterEach(function (): void {
    global $herdTestCleanupData;

    foreach ($herdTestCleanupData['originalEnv'] as $key => $value) {
        if ($value === null) {
            unset($_SERVER[$key]);
        } else {
            $_SERVER[$key] = $value;
        }
    }

    // Clean up temp directory
    if (is_dir($herdTestCleanupData['tempDir'])) {
        removeHerdTestDirectory($herdTestCleanupData['tempDir']);
    }

    $herdTestCleanupData = [];
});

function removeHerdTestDirectory(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir.DIRECTORY_SEPARATOR.$file;
        is_dir($path) ? removeHerdTestDirectory($path) : unlink($path);
    }

    rmdir($dir);
}

function initializeHerdTestEnvironment(): array
{
    return [
        'originalEnv' => [
            'HOME' => $_SERVER['HOME'] ?? null,
            'USERPROFILE' => $_SERVER['USERPROFILE'] ?? null,
        ],
        'tempDir' => sys_get_temp_dir().'/herd_test_'.uniqid().'_'.getmypid(),
    ];
}

function getHerdTestTempDir(): string
{
    global $herdTestCleanupData;

    return $herdTestCleanupData['tempDir'];
}

test('mcpPath builds correct Windows path from USERPROFILE when HOME missing', function (): void {
    unset($_SERVER['HOME']);
    $_SERVER['USERPROFILE'] = 'C:\\Users\\TestUser';

    $herd = new Herd;
    $expected = 'C:/Users/TestUser/.config/herd/bin/herd-mcp.phar';

    expect($herd->mcpPath())->toBe($expected);
})->onlyOnWindows();

test('isMcpAvailable returns false when MCP file is missing from home', function (): void {
    $testHome = getHerdTestTempDir().'/home';
    mkdir($testHome, 0755, true);
    $_SERVER['HOME'] = $testHome;

    $herd = new Herd;

    expect($herd->isMcpAvailable())->toBeFalse();
})->onlyOnWindows();

test('isMcpAvailable returns true when MCP file exists in home', function (): void {
    $testHome = getHerdTestTempDir().'/home';
    mkdir($testHome, 0755, true);
    $_SERVER['HOME'] = $testHome;

    $herd = new Herd;
    $mcpPath = $herd->mcpPath();

    $mcpDir = dirname($mcpPath);
    mkdir($mcpDir, 0755, true);

    file_put_contents($mcpPath, 'test phar content');

    expect($herd->isMcpAvailable())->toBeTrue();
})->onlyOnWindows();

test('isMcpAvailable returns false after MCP file is removed', function (): void {
    $testHome = getHerdTestTempDir().'/home';
    mkdir($testHome, 0755, true);
    $_SERVER['HOME'] = $testHome;

    $herd = new Herd;
    $mcpPath = $herd->mcpPath();

    $mcpDir = dirname($mcpPath);
    mkdir($mcpDir, 0755, true);
    file_put_contents($mcpPath, 'test phar content');

    expect($herd->isMcpAvailable())->toBeTrue();

    // Remove file
    unlink($mcpPath);

    expect($herd->isMcpAvailable())->toBeFalse();
})->onlyOnWindows();

test('getHomePath returns HOME on non-Windows', function (): void {
    $testHome = getHerdTestTempDir().'/home';
    mkdir($testHome, 0755, true);
    $_SERVER['HOME'] = $testHome;

    $herd = new Herd;

    expect($herd->getHomePath())->toBe($testHome);
})->skipOnWindows();

test('getHomePath uses USERPROFILE on Windows when HOME is not set and normalizes slashes', function (): void {
    unset($_SERVER['HOME']);
    $_SERVER['USERPROFILE'] = 'C:\\Users\\TestUser';

    $herd = new Herd;

    expect($herd->getHomePath())->toBe('C:/Users/TestUser');
})->onlyOnWindows();

test('isInstalled returns true when herd config directory exists on Windows', function (): void {
    $testHome = getHerdTestTempDir().'/home';
    mkdir($testHome, 0755, true);
    $_SERVER['HOME'] = $testHome;

    $configDir = $testHome.'/.config/herd';
    mkdir($configDir, 0755, true);

    $herd = new Herd;

    expect($herd->isInstalled())->toBeTrue();
})->onlyOnWindows();

test('isInstalled returns false when herd config directory is missing on Windows', function (): void {
    $testHome = getHerdTestTempDir().'/home';
    mkdir($testHome, 0755, true);
    $_SERVER['HOME'] = $testHome;

    $herd = new Herd;

    expect($herd->isInstalled())->toBeFalse();
})->onlyOnWindows();

test('isWindowsPlatform returns true on Windows', function (): void {
    $herd = new Herd;

    expect($herd->isWindowsPlatform())->toBeTrue();
})->onlyOnWindows();

test('isWindowsPlatform returns false on non-Windows platforms', function (): void {
    $herd = new Herd;

    expect($herd->isWindowsPlatform())->toBeFalse();
})->skipOnWindows();
