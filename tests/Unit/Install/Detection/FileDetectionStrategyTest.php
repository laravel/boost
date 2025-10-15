<?php

declare(strict_types=1);

use Laravel\Boost\Install\Detection\FileDetectionStrategy;

beforeEach(function (): void {
    $this->strategy = new FileDetectionStrategy;
    $this->tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($this->tempDir);
});

afterEach(function (): void {
    if (is_dir($this->tempDir) && str_contains($this->tempDir, sys_get_temp_dir())) {
        removeDirectoryForFileTests($this->tempDir);
    }
});

test('detects existing file', function (): void {
    file_put_contents($this->tempDir.'/test.txt', 'test content');

    $result = $this->strategy->detect([
        'files' => ['test.txt'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeTrue();
});

test('fails for non existent file', function (): void {
    $result = $this->strategy->detect([
        'files' => ['non_existent.txt'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeFalse();
});

test('detects multiple files first exists', function (): void {
    file_put_contents($this->tempDir.'/first.txt', 'content');

    $result = $this->strategy->detect([
        'files' => ['first.txt', 'second.txt'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeTrue();
});

test('detects multiple files second exists', function (): void {
    file_put_contents($this->tempDir.'/second.txt', 'content');

    $result = $this->strategy->detect([
        'files' => ['first.txt', 'second.txt'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeTrue();
});

test('fails when no files exist', function (): void {
    $result = $this->strategy->detect([
        'files' => ['missing1.txt', 'missing2.txt'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeFalse();
});

test('returns false when no files config', function (): void {
    $result = $this->strategy->detect([
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeFalse();
});

test('uses current directory when no base path', function (): void {
    // This test creates a file in the current working directory
    $currentDir = getcwd();
    $testFile = $currentDir.'/temp_test_file.txt';
    file_put_contents($testFile, 'test');

    try {
        $result = $this->strategy->detect([
            'files' => ['temp_test_file.txt'],
        ]);

        expect($result)->toBeTrue();
    } finally {
        unlink($testFile);
    }
});

test('detects files in subdirectories', function (): void {
    mkdir($this->tempDir.'/subdir');
    file_put_contents($this->tempDir.'/subdir/nested.txt', 'content');

    $result = $this->strategy->detect([
        'files' => ['subdir/nested.txt'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeTrue();
});

test('handles empty files array', function (): void {
    $result = $this->strategy->detect([
        'files' => [],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeFalse();
});

test('detects files with special characters', function (): void {
    file_put_contents($this->tempDir.'/file-with_special.chars.txt', 'content');

    $result = $this->strategy->detect([
        'files' => ['file-with_special.chars.txt'],
        'basePath' => $this->tempDir,
    ]);

    expect($result)->toBeTrue();
});

function removeDirectoryForFileTests(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir.DIRECTORY_SEPARATOR.$file;
        is_dir($path) ? removeDirectoryForFileTests($path) : unlink($path);
    }

    rmdir($dir);
}
