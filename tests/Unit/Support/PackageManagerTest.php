<?php

declare(strict_types=1);

use Laravel\Boost\Support\PackageManager;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($this->tempDir);
});

afterEach(function (): void {
    if (is_dir($this->tempDir) && str_contains($this->tempDir, sys_get_temp_dir())) {
        removeDirectoryForPackageManagerTests($this->tempDir);
    }
});

test('detects bun when bun.lockb exists', function (): void {
    file_put_contents($this->tempDir.'/bun.lockb', '');

    $result = PackageManager::detect($this->tempDir);

    expect($result)->toBe('bun');
});

test('detects pnpm when pnpm-lock.yaml exists', function (): void {
    file_put_contents($this->tempDir.'/pnpm-lock.yaml', '');

    $result = PackageManager::detect($this->tempDir);

    expect($result)->toBe('pnpm');
});

test('detects yarn when yarn.lock exists', function (): void {
    file_put_contents($this->tempDir.'/yarn.lock', '');

    $result = PackageManager::detect($this->tempDir);

    expect($result)->toBe('yarn');
});

test('detects npm when package-lock.json exists', function (): void {
    file_put_contents($this->tempDir.'/package-lock.json', '');

    $result = PackageManager::detect($this->tempDir);

    expect($result)->toBe('npm');
});

test('defaults to npm when no lockfiles exist', function (): void {
    $result = PackageManager::detect($this->tempDir);

    expect($result)->toBe('npm');
});

test('prioritizes bun over other package managers', function (): void {
    file_put_contents($this->tempDir.'/bun.lockb', '');
    file_put_contents($this->tempDir.'/pnpm-lock.yaml', '');
    file_put_contents($this->tempDir.'/yarn.lock', '');
    file_put_contents($this->tempDir.'/package-lock.json', '');

    $result = PackageManager::detect($this->tempDir);

    expect($result)->toBe('bun');
});

test('prioritizes pnpm over yarn and npm', function (): void {
    file_put_contents($this->tempDir.'/pnpm-lock.yaml', '');
    file_put_contents($this->tempDir.'/yarn.lock', '');
    file_put_contents($this->tempDir.'/package-lock.json', '');

    $result = PackageManager::detect($this->tempDir);

    expect($result)->toBe('pnpm');
});

test('prioritizes yarn over npm', function (): void {
    file_put_contents($this->tempDir.'/yarn.lock', '');
    file_put_contents($this->tempDir.'/package-lock.json', '');

    $result = PackageManager::detect($this->tempDir);

    expect($result)->toBe('yarn');
});

test('uses base_path when no basePath parameter is provided', function (): void {
    // When no parameter is provided, it should use base_path() and return one of the valid package managers
    $result = PackageManager::detect();

    expect($result)->toBeIn(['npm', 'bun', 'pnpm', 'yarn']);
});

function removeDirectoryForPackageManagerTests(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir.DIRECTORY_SEPARATOR.$file;
        is_dir($path) ? removeDirectoryForPackageManagerTests($path) : unlink($path);
    }

    rmdir($dir);
}
