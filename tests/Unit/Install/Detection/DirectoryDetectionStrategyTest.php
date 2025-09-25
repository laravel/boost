<?php

declare(strict_types=1);

use Laravel\Boost\Install\Detection\DirectoryDetectionStrategy;
use Laravel\Boost\Install\Enums\Platform;

beforeEach(function (): void {
    $this->strategy = new DirectoryDetectionStrategy;
    $this->tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($this->tempDir);
});

afterEach(function (): void {
    if (is_dir($this->tempDir)) {
        removeDirectory($this->tempDir);
    }
});

test('detects existing directory', function (): void {
    $testDir = $this->tempDir.'/test_app';
    mkdir($testDir);

    $result = $this->strategy->detect([
        'paths' => ['test_app'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeTrue();
});

test('fails for non existent directory', function (): void {
    $result = $this->strategy->detect([
        'paths' => ['non_existent'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeFalse();
});

test('detects absolute path', function (): void {
    $testDir = $this->tempDir.'/absolute_test';
    mkdir($testDir);

    $result = $this->strategy->detect([
        'paths' => [$testDir],
    ]);

    expect($result)->toBeTrue();
});

test('detects multiple paths first exists', function (): void {
    $testDir = $this->tempDir.'/first_exists';
    mkdir($testDir);

    $result = $this->strategy->detect([
        'paths' => ['first_exists', 'second_missing'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeTrue();
});

test('detects multiple paths second exists', function (): void {
    $testDir = $this->tempDir.'/second_exists';
    mkdir($testDir);

    $result = $this->strategy->detect([
        'paths' => ['first_missing', 'second_exists'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeTrue();
});

test('fails when no paths exist', function (): void {
    $result = $this->strategy->detect([
        'paths' => ['missing1', 'missing2'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeFalse();
});

test('returns false when no paths config', function (): void {
    $result = $this->strategy->detect([
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeFalse();
});

test('uses current directory when no base path', function (): void {
    // This test creates a directory in the current working directory
    $currentDir = getcwd();
    $testDir = $currentDir.'/temp_test_dir';
    mkdir($testDir);

    try {
        $result = $this->strategy->detect([
            'paths' => ['temp_test_dir'],
        ]);

        expect($result)->toBeTrue();
    } finally {
        rmdir($testDir);
    }
});

test('detects with glob pattern', function (): void {
    // Create test directories with patterns
    mkdir($this->tempDir.'/app_v1');
    mkdir($this->tempDir.'/app_v2');

    $result = $this->strategy->detect([
        'paths' => ['app_v*'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeTrue();
});

test('fails with glob pattern no matches', function (): void {
    $result = $this->strategy->detect([
        'paths' => ['nonexistent_*'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeFalse();
});

test('expands tilde home directory', function (): void {
    // Mock HOME environment variable
    $originalHome = getenv('HOME');
    putenv('HOME='.$this->tempDir);

    mkdir($this->tempDir.'/test_home');

    try {
        $result = $this->strategy->detect([
            'paths' => ['~/test_home'],
        ]);

        expect($result)->toBeTrue();
    } finally {
        // Restore original HOME
        if ($originalHome !== false) {
            putenv('HOME='.$originalHome);
        } else {
            putenv('HOME');
        }
    }
});

test('expands windows environment variables', function (): void {
    // Mock environment variable for Windows
    putenv('TESTVAR='.$this->tempDir);
    mkdir($this->tempDir.'/windows_test');

    try {
        $result = $this->strategy->detect([
            'paths' => ['%TESTVAR%/windows_test'],
        ], Platform::Windows);

        expect($result)->toBeTrue();
    } finally {
        putenv('TESTVAR');
    }
});

test('handles missing environment variable on windows', function (): void {
    $result = $this->strategy->detect([
        'paths' => ['%NONEXISTENT%/test'],
    ], Platform::Windows);

    expect($result)->toBeFalse();
});

test('identifies absolute paths correctly', function (): void {
    $reflectionClass = new \ReflectionClass($this->strategy);
    $isAbsolutePathMethod = $reflectionClass->getMethod('isAbsolutePath');

    // Unix absolute paths
    expect($isAbsolutePathMethod->invoke($this->strategy, '/usr/local/bin'))->toBeTrue();

    // Windows absolute paths
    expect($isAbsolutePathMethod->invoke($this->strategy, 'C:\\Program Files'))->toBeTrue()
        ->and($isAbsolutePathMethod->invoke($this->strategy, 'D:\\test'))->toBeTrue();

    // Relative paths
    expect($isAbsolutePathMethod->invoke($this->strategy, 'relative/path'))->toBeFalse()
        ->and($isAbsolutePathMethod->invoke($this->strategy, './relative'))->toBeFalse()
        ->and($isAbsolutePathMethod->invoke($this->strategy, '../relative'))->toBeFalse();
});

function removeDirectory(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir.DIRECTORY_SEPARATOR.$file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }

    rmdir($dir);
}
