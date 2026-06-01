<?php

declare(strict_types=1);

use Laravel\Boost\Install\Sail;

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

describe('isInstalled', function (): void {
    beforeEach(function (): void {
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost_sail_'.uniqid();
        mkdir($this->tempDir);
        app()->setBasePath($this->tempDir);

        $this->binary = $this->tempDir.DIRECTORY_SEPARATOR.'sail';
        config(['boost.executable_paths.sail' => $this->binary]);
        touch($this->binary);
    });

    afterEach(function (): void {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        ) as $path) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }

        rmdir($this->tempDir);
    });

    test('detects every Docker Compose filename supported by default', function (string $composeFile): void {
        touch($this->tempDir.DIRECTORY_SEPARATOR.$composeFile);

        expect((new Sail)->isInstalled())->toBeTrue();
    })->with([
        'compose.yml',
        'compose.yaml',
        'docker-compose.yml',
        'docker-compose.yaml',
    ]);

    test('returns false when no Docker Compose file is present', function (): void {
        expect((new Sail)->isInstalled())->toBeFalse();
    });

    test('returns false when the sail binary is missing', function (): void {
        unlink($this->binary);

        touch($this->tempDir.DIRECTORY_SEPARATOR.'docker-compose.yml');

        expect((new Sail)->isInstalled())->toBeFalse();
    });
});
