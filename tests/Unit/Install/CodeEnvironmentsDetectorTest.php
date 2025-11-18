<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\CodeEnvironment\ClaudeCode;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\CodeEnvironment\Codex;
use Laravel\Boost\Install\CodeEnvironment\Copilot;
use Laravel\Boost\Install\CodeEnvironment\Cursor;
use Laravel\Boost\Install\CodeEnvironment\Gemini;
use Laravel\Boost\Install\CodeEnvironment\OpenCode;
use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\CodeEnvironment\VSCode;
use Laravel\Boost\Install\CodeEnvironmentsDetector;
use Laravel\Boost\Install\Enums\Platform;

beforeEach(function (): void {
    $this->container = new Container;
    $this->boostManager = new BoostManager;
    $this->detector = new CodeEnvironmentsDetector($this->container, $this->boostManager);
});

afterEach(function (): void {
    Mockery::close();
});

it('returns collection of all registered code environments', function (): void {
    $codeEnvironments = $this->detector->getCodeEnvironments();

    expect($codeEnvironments)->toBeInstanceOf(Collection::class)
        ->and($codeEnvironments->count())->toBe(8)
        ->and($codeEnvironments->keys()->toArray())->toBe([
            'phpstorm', 'vscode', 'cursor', 'claudecode', 'codex', 'copilot', 'opencode', 'gemini',
        ]);

    $codeEnvironments->each(function ($environment): void {
        expect($environment)->toBeInstanceOf(CodeEnvironment::class);
    });
});

it('returns an array of detected environment names for system discovery', function (): void {
    $mockPhpStorm = Mockery::mock(CodeEnvironment::class);
    $mockPhpStorm->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(true);
    $mockPhpStorm->shouldReceive('name')->andReturn('phpstorm');

    $mockVSCode = Mockery::mock(CodeEnvironment::class);
    $mockVSCode->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $mockVSCode->shouldReceive('name')->andReturn('vscode');

    $mockCursor = Mockery::mock(CodeEnvironment::class);
    $mockCursor->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(true);
    $mockCursor->shouldReceive('name')->andReturn('cursor');

    $mockOther = Mockery::mock(CodeEnvironment::class);
    $mockOther->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $mockOther->shouldReceive('name')->andReturn('other');

    $this->container->bind(PhpStorm::class, fn () => $mockPhpStorm);
    $this->container->bind(VSCode::class, fn () => $mockVSCode);
    $this->container->bind(Cursor::class, fn () => $mockCursor);
    $this->container->bind(ClaudeCode::class, fn () => $mockOther);
    $this->container->bind(Codex::class, fn () => $mockOther);
    $this->container->bind(Copilot::class, fn () => $mockOther);
    $this->container->bind(OpenCode::class, fn () => $mockOther);
    $this->container->bind(Gemini::class, fn () => $mockOther);

    $detector = new CodeEnvironmentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverSystemInstalledCodeEnvironments();

    expect($detected)->toBe(['phpstorm', 'cursor']);
});

it('returns an empty array when no environments are detected for system discovery', function (): void {
    $mockEnvironment = Mockery::mock(CodeEnvironment::class);
    $mockEnvironment->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $mockEnvironment->shouldReceive('name')->andReturn('mock');

    $this->container->bind(PhpStorm::class, fn () => $mockEnvironment);
    $this->container->bind(VSCode::class, fn () => $mockEnvironment);
    $this->container->bind(Cursor::class, fn () => $mockEnvironment);
    $this->container->bind(ClaudeCode::class, fn () => $mockEnvironment);
    $this->container->bind(Codex::class, fn () => $mockEnvironment);
    $this->container->bind(Copilot::class, fn () => $mockEnvironment);
    $this->container->bind(OpenCode::class, fn () => $mockEnvironment);
    $this->container->bind(Gemini::class, fn () => $mockEnvironment);

    $detector = new CodeEnvironmentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverSystemInstalledCodeEnvironments();

    expect($detected)->toBe([]);
});

it('returns an array of detected environment names for project discovery', function (): void {
    $basePath = '/test/project';

    $mockVSCode = Mockery::mock(CodeEnvironment::class);
    $mockVSCode->shouldReceive('detectInProject')->with($basePath)->andReturn(true);
    $mockVSCode->shouldReceive('name')->andReturn('vscode');

    $mockPhpStorm = Mockery::mock(CodeEnvironment::class);
    $mockPhpStorm->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $mockPhpStorm->shouldReceive('name')->andReturn('phpstorm');

    $mockClaudeCode = Mockery::mock(CodeEnvironment::class);
    $mockClaudeCode->shouldReceive('detectInProject')->with($basePath)->andReturn(true);
    $mockClaudeCode->shouldReceive('name')->andReturn('claudecode');

    $mockOther = Mockery::mock(CodeEnvironment::class);
    $mockOther->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $mockOther->shouldReceive('name')->andReturn('other');

    $this->container->bind(PhpStorm::class, fn () => $mockPhpStorm);
    $this->container->bind(VSCode::class, fn () => $mockVSCode);
    $this->container->bind(Cursor::class, fn () => $mockOther);
    $this->container->bind(ClaudeCode::class, fn () => $mockClaudeCode);
    $this->container->bind(Codex::class, fn () => $mockOther);
    $this->container->bind(Copilot::class, fn () => $mockOther);
    $this->container->bind(OpenCode::class, fn () => $mockOther);
    $this->container->bind(Gemini::class, fn () => $mockOther);

    $detector = new CodeEnvironmentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverProjectInstalledCodeEnvironments($basePath);

    expect($detected)->toBe(['vscode', 'claudecode']);
});

it('returns an empty array when no environments are detected for project discovery', function (): void {
    $basePath = '/empty/project';

    $mockEnvironment = Mockery::mock(CodeEnvironment::class);
    $mockEnvironment->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $mockEnvironment->shouldReceive('name')->andReturn('mock');

    $this->container->bind(PhpStorm::class, fn () => $mockEnvironment);
    $this->container->bind(VSCode::class, fn () => $mockEnvironment);
    $this->container->bind(Cursor::class, fn () => $mockEnvironment);
    $this->container->bind(ClaudeCode::class, fn () => $mockEnvironment);
    $this->container->bind(Codex::class, fn () => $mockEnvironment);
    $this->container->bind(Copilot::class, fn () => $mockEnvironment);
    $this->container->bind(OpenCode::class, fn () => $mockEnvironment);
    $this->container->bind(Gemini::class, fn () => $mockEnvironment);

    $detector = new CodeEnvironmentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverProjectInstalledCodeEnvironments($basePath);

    expect($detected)->toBe([]);
});
