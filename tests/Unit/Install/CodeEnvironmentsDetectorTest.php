<?php

declare(strict_types=1);

use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\CodeEnvironmentsDetector;
use Laravel\Boost\Install\Enums\Platform;

beforeEach(function () {
    $this->container = new \Illuminate\Container\Container;
    $this->detector = new CodeEnvironmentsDetector($this->container);
});

afterEach(function () {
    Mockery::close();
});

test('discoverSystemInstalledCodeEnvironments returns detected programs', function () {
    // Create mock programs
    $program1 = Mockery::mock(CodeEnvironment::class);
    $program1->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(true);
    $program1->shouldReceive('name')->andReturn('phpstorm');

    $program2 = Mockery::mock(CodeEnvironment::class);
    $program2->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $program2->shouldReceive('name')->andReturn('vscode');

    $program3 = Mockery::mock(CodeEnvironment::class);
    $program3->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(true);
    $program3->shouldReceive('name')->andReturn('cursor');

    // Mock all other programs that might be instantiated
    $otherProgram = Mockery::mock(CodeEnvironment::class);
    $otherProgram->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $otherProgram->shouldReceive('name')->andReturn('other');

    // Bind mocked programs to container
    $container = new \Illuminate\Container\Container;
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\PhpStorm::class, fn () => $program1);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\VSCode::class, fn () => $program2);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\Cursor::class, fn () => $program3);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\ClaudeCode::class, fn () => $otherProgram);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\Copilot::class, fn () => $otherProgram);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\Trae::class, fn () => $otherProgram);

    $detector = new CodeEnvironmentsDetector($container);
    $detected = $detector->discoverSystemInstalledCodeEnvironments();

    expect($detected)->toBe(['phpstorm', 'cursor']);
});

test('discoverSystemInstalledCodeEnvironments returns empty array when no programs detected', function () {
    $program1 = Mockery::mock(CodeEnvironment::class);
    $program1->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $program1->shouldReceive('name')->andReturn('phpstorm');

    // Mock all other programs that might be instantiated
    $otherProgram = Mockery::mock(CodeEnvironment::class);
    $otherProgram->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $otherProgram->shouldReceive('name')->andReturn('other');

    // Bind mocked program to container
    $container = new \Illuminate\Container\Container;
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\PhpStorm::class, fn () => $program1);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\VSCode::class, fn () => $otherProgram);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\Cursor::class, fn () => $otherProgram);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\ClaudeCode::class, fn () => $otherProgram);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\Copilot::class, fn () => $otherProgram);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\Trae::class, fn () => $otherProgram);

    $detector = new CodeEnvironmentsDetector($container);
    $detected = $detector->discoverSystemInstalledCodeEnvironments();

    expect($detected)->toBeEmpty();
});

test('discoverProjectInstalledCodeEnvironments detects programs in project', function () {
    $basePath = '/path/to/project';

    $program1 = Mockery::mock(CodeEnvironment::class);
    $program1->shouldReceive('detectInProject')->with($basePath)->andReturn(true);
    $program1->shouldReceive('name')->andReturn('vscode');

    $program2 = Mockery::mock(CodeEnvironment::class);
    $program2->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $program2->shouldReceive('name')->andReturn('phpstorm');

    $program3 = Mockery::mock(CodeEnvironment::class);
    $program3->shouldReceive('detectInProject')->with($basePath)->andReturn(true);
    $program3->shouldReceive('name')->andReturn('claudecode');

    // Bind mocked programs to container
    $container = new \Illuminate\Container\Container;
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\VSCode::class, fn () => $program1);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\PhpStorm::class, fn () => $program2);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\ClaudeCode::class, fn () => $program3);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\Trae::class, fn () => $program2);

    $detector = new CodeEnvironmentsDetector($container);
    $detected = $detector->discoverProjectInstalledCodeEnvironments($basePath);

    expect($detected)->toBe(['vscode', 'claudecode']);
});

test('discoverProjectInstalledCodeEnvironments returns empty array when no programs detected in project', function () {
    $basePath = '/path/to/project';

    $program1 = Mockery::mock(CodeEnvironment::class);
    $program1->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $program1->shouldReceive('name')->andReturn('vscode');

    // Bind mocked program to container
    $container = new \Illuminate\Container\Container;
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\VSCode::class, fn () => $program1);
    $container->bind(\Laravel\Boost\Install\CodeEnvironment\Trae::class, fn () => $program1);

    $detector = new CodeEnvironmentsDetector($container);
    $detected = $detector->discoverProjectInstalledCodeEnvironments($basePath);

    expect($detected)->toBeEmpty();
});

test('discoverProjectInstalledCodeEnvironments detects applications by directory', function () {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.'/.vscode');

    $detected = $this->detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($detected)->toContain('vscode');

    // Cleanup
    rmdir($tempDir.'/.vscode');
    rmdir($tempDir);
});

test('discoverProjectInstalledCodeEnvironments detects applications with mixed type', function () {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($tempDir);
    file_put_contents($tempDir.'/CLAUDE.md', 'test');

    $detected = $this->detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($detected)->toContain('claudecode');

    // Cleanup
    unlink($tempDir.'/CLAUDE.md');
    rmdir($tempDir);
});

test('discoverProjectInstalledCodeEnvironments detects copilot with nested file path', function () {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.'/.github');
    file_put_contents($tempDir.'/.github/copilot-instructions.md', 'test');

    $detected = $this->detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($detected)->toContain('copilot');

    // Cleanup
    unlink($tempDir.'/.github/copilot-instructions.md');
    rmdir($tempDir.'/.github');
    rmdir($tempDir);
});

test('discoverProjectInstalledCodeEnvironments detects claude code with directory', function () {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.'/.claude');

    $detected = $this->detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($detected)->toContain('claudecode');

    // Cleanup
    rmdir($tempDir.'/.claude');
    rmdir($tempDir);
});

test('discoverProjectInstalledCodeEnvironments detects phpstorm with idea directory', function () {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.'/.idea');

    $detected = $this->detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($detected)->toContain('phpstorm');

    // Cleanup
    rmdir($tempDir.'/.idea');
    rmdir($tempDir);
});

test('discoverProjectInstalledCodeEnvironments detects phpstorm with junie directory', function () {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.'/.junie');

    $detected = $this->detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($detected)->toContain('phpstorm');

    // Cleanup
    rmdir($tempDir.'/.junie');
    rmdir($tempDir);
});

test('discoverProjectInstalledCodeEnvironments detects cursor with cursor directory', function () {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.'/.cursor');

    $detected = $this->detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($detected)->toContain('cursor');

    // Cleanup
    rmdir($tempDir.'/.cursor');
    rmdir($tempDir);
});

test('discoverProjectInstalledCodeEnvironments detects trae with trae directory', function () {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.'/.trae');

    $detected = $this->detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($detected)->toContain('trae');

    // Cleanup
    rmdir($tempDir.'/.trae');
    rmdir($tempDir);
});

test('discoverProjectInstalledCodeEnvironments handles multiple detections', function () {
    $tempDir = sys_get_temp_dir().'/boost_test_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.'/.vscode');
    mkdir($tempDir.'/.cursor');
    mkdir($tempDir.'/.trae');
    file_put_contents($tempDir.'/CLAUDE.md', 'test');

    $detected = $this->detector->discoverProjectInstalledCodeEnvironments($tempDir);

    expect($detected)->toContain('vscode');
    expect($detected)->toContain('cursor');
    expect($detected)->toContain('trae');
    expect($detected)->toContain('claudecode');
    expect(count($detected))->toBeGreaterThanOrEqual(4);

    // Cleanup
    rmdir($tempDir.'/.vscode');
    rmdir($tempDir.'/.cursor');
    rmdir($tempDir.'/.trae');
    unlink($tempDir.'/CLAUDE.md');
    rmdir($tempDir);
});
