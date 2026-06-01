<?php

declare(strict_types=1);

use Laravel\Boost\Install\Sail;

$sailTempDir = null;

beforeEach(function (): void {
    global $sailTempDir;

    $sailTempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost_sail_'.uniqid();
    mkdir($sailTempDir);
    app()->setBasePath($sailTempDir);

    $binary = $sailTempDir.DIRECTORY_SEPARATOR.'sail';
    config(['boost.executable_paths.sail' => $binary]);
    touch($binary);
});

afterEach(function (): void {
    global $sailTempDir;

    if (is_dir($sailTempDir)) {
        removeSailTestDirectory($sailTempDir);
    }

    $sailTempDir = null;
});

function removeSailTestDirectory(string $dir): void
{
    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir.DIRECTORY_SEPARATOR.$file;
        is_dir($path) ? removeSailTestDirectory($path) : unlink($path);
    }

    rmdir($dir);
}

test('isActive returns true when LARAVEL_SAIL env var is set', function (): void {
    putenv('LARAVEL_SAIL=1');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(false);

    expect($sail->isActive())->toBeTrue();

    putenv('LARAVEL_SAIL=');
});

test('isActive returns false when running in devcontainer without LARAVEL_SAIL', function (): void {
    putenv('LARAVEL_SAIL=');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(true);

    expect($sail->isActive())->toBeFalse();
});

test('isActive returns false when LARAVEL_SAIL is set inside a devcontainer', function (): void {
    putenv('LARAVEL_SAIL=1');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(true);

    expect($sail->isActive())->toBeFalse();

    putenv('LARAVEL_SAIL=');
});

test('isActive returns false when not sail user and no env var and not in container', function (): void {
    putenv('LARAVEL_SAIL=');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(false);

    // get_current_user() won't return 'sail' in the test environment
    expect($sail->isActive())->toBeFalse();
});

test('isRunningInDevcontainer returns true when REMOTE_CONTAINERS is true', function (): void {
    putenv('REMOTE_CONTAINERS=true');

    expect((new Sail)->isRunningInDevcontainer())->toBeTrue();

    putenv('REMOTE_CONTAINERS=');
});

test('isRunningInDevcontainer returns false when REMOTE_CONTAINERS is not set', function (): void {
    putenv('REMOTE_CONTAINERS=');

    expect((new Sail)->isRunningInDevcontainer())->toBeFalse();
});

test('sail binary path can be overridden via config', function (): void {
    config(['boost.executable_paths.sail' => '/custom/path/sail']);

    expect(Sail::binaryPath())->toBe('/custom/path/sail');
});

test('sail binary path defaults to vendor/bin/sail', function (): void {
    config(['boost.executable_paths.sail' => null]);

    expect(Sail::binaryPath())->toBe('vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'sail');
});

test('isInstalled detects every Docker Compose filename supported by default', function (string $composeFile): void {
    global $sailTempDir;

    touch($sailTempDir.DIRECTORY_SEPARATOR.$composeFile);

    expect((new Sail)->isInstalled())->toBeTrue();
})->with([
    'compose.yml',
    'compose.yaml',
    'docker-compose.yml',
    'docker-compose.yaml',
]);

test('isInstalled returns false when no Docker Compose file is present', function (): void {
    expect((new Sail)->isInstalled())->toBeFalse();
});

test('isInstalled returns false when the sail binary is missing', function (): void {
    global $sailTempDir;

    unlink($sailTempDir.DIRECTORY_SEPARATOR.'sail');

    touch($sailTempDir.DIRECTORY_SEPARATOR.'docker-compose.yml');

    expect((new Sail)->isInstalled())->toBeFalse();
});
