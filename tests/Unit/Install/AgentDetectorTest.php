<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\Agents\ClaudeCode;
use Laravel\Boost\Install\Agents\Codex;
use Laravel\Boost\Install\Agents\Copilot;
use Laravel\Boost\Install\Agents\Cursor;
use Laravel\Boost\Install\Agents\PhpStorm;
use Laravel\Boost\Install\Agents\VSCode;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Enums\Platform;

beforeEach(function (): void {
    $this->container = new Container;
    $this->boostManager = new BoostManager;
    $this->detector = new AgentsDetector($this->container, $this->boostManager);
});

afterEach(function (): void {
    Mockery::close();
});

it('returns a collection of all registered agents', function (): void {
    $agents = $this->detector->getAgents();

    expect($agents)->toBeInstanceOf(Collection::class)
        ->and($agents->count())->toBe(6)
        ->and($agents->keys()->toArray())->toBe([
            'phpstorm', 'vscode', 'cursor', 'claude_code', 'codex', 'copilot',
        ]);

    $agents->each(function ($environment): void {
        expect($environment)->toBeInstanceOf(Agent::class);
    });
});

it('returns an array of detected environment names for system discovery', function (): void {
    $mockPhpStorm = Mockery::mock(Agent::class);
    $mockPhpStorm->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(true);
    $mockPhpStorm->shouldReceive('name')->andReturn('phpstorm');

    $mockVSCode = Mockery::mock(Agent::class);
    $mockVSCode->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $mockVSCode->shouldReceive('name')->andReturn('vscode');

    $mockCursor = Mockery::mock(Agent::class);
    $mockCursor->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(true);
    $mockCursor->shouldReceive('name')->andReturn('cursor');

    $mockOther = Mockery::mock(Agent::class);
    $mockOther->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $mockOther->shouldReceive('name')->andReturn('other');

    $this->container->bind(PhpStorm::class, fn () => $mockPhpStorm);
    $this->container->bind(VSCode::class, fn () => $mockVSCode);
    $this->container->bind(Cursor::class, fn () => $mockCursor);
    $this->container->bind(ClaudeCode::class, fn () => $mockOther);
    $this->container->bind(Codex::class, fn () => $mockOther);
    $this->container->bind(Copilot::class, fn () => $mockOther);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverSystemInstalledAgents();

    expect($detected)->toBe(['phpstorm', 'cursor']);
});

it('returns an empty array when no environments are detected for system discovery', function (): void {
    $mockEnvironment = Mockery::mock(Agent::class);
    $mockEnvironment->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $mockEnvironment->shouldReceive('name')->andReturn('mock');

    $this->container->bind(PhpStorm::class, fn () => $mockEnvironment);
    $this->container->bind(VSCode::class, fn () => $mockEnvironment);
    $this->container->bind(Cursor::class, fn () => $mockEnvironment);
    $this->container->bind(ClaudeCode::class, fn () => $mockEnvironment);
    $this->container->bind(Codex::class, fn () => $mockEnvironment);
    $this->container->bind(Copilot::class, fn () => $mockEnvironment);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverSystemInstalledAgents();

    expect($detected)->toBe([]);
});

it('returns an array of detected environment names for project discovery', function (): void {
    $basePath = '/test/project';

    $mockVSCode = Mockery::mock(Agent::class);
    $mockVSCode->shouldReceive('detectInProject')->with($basePath)->andReturn(true);
    $mockVSCode->shouldReceive('name')->andReturn('vscode');

    $mockPhpStorm = Mockery::mock(Agent::class);
    $mockPhpStorm->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $mockPhpStorm->shouldReceive('name')->andReturn('phpstorm');

    $mockClaudeCode = Mockery::mock(Agent::class);
    $mockClaudeCode->shouldReceive('detectInProject')->with($basePath)->andReturn(true);
    $mockClaudeCode->shouldReceive('name')->andReturn('claudecode');

    $mockOther = Mockery::mock(Agent::class);
    $mockOther->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $mockOther->shouldReceive('name')->andReturn('other');

    $this->container->bind(PhpStorm::class, fn () => $mockPhpStorm);
    $this->container->bind(VSCode::class, fn () => $mockVSCode);
    $this->container->bind(Cursor::class, fn () => $mockOther);
    $this->container->bind(ClaudeCode::class, fn () => $mockClaudeCode);
    $this->container->bind(Codex::class, fn () => $mockOther);
    $this->container->bind(Copilot::class, fn () => $mockOther);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverProjectInstalledAgents($basePath);

    expect($detected)->toBe(['vscode', 'claudecode']);
});

it('returns an empty array when no environments are detected for project discovery', function (): void {
    $basePath = '/empty/project';

    $mockEnvironment = Mockery::mock(Agent::class);
    $mockEnvironment->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $mockEnvironment->shouldReceive('name')->andReturn('mock');

    $this->container->bind(PhpStorm::class, fn () => $mockEnvironment);
    $this->container->bind(VSCode::class, fn () => $mockEnvironment);
    $this->container->bind(Cursor::class, fn () => $mockEnvironment);
    $this->container->bind(ClaudeCode::class, fn () => $mockEnvironment);
    $this->container->bind(Codex::class, fn () => $mockEnvironment);
    $this->container->bind(Copilot::class, fn () => $mockEnvironment);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverProjectInstalledAgents($basePath);

    expect($detected)->toBe([]);
});
