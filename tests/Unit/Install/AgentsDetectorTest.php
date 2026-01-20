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
use Laravel\Boost\Install\Agents\Gemini;
use Laravel\Boost\Install\Agents\Junie;
use Laravel\Boost\Install\Agents\OpenCode;
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

it('returns collection of all registered agents', function (): void {
    $agents = $this->detector->getAgents();

    expect($agents)->toBeInstanceOf(Collection::class)
        ->and($agents->count())->toBe(7)
        ->and($agents->keys()->toArray())->toBe([
            'junie', 'cursor', 'claude_code', 'codex', 'copilot', 'opencode', 'gemini',
        ]);

    $agents->each(function ($agent): void {
        expect($agent)->toBeInstanceOf(Agent::class);
    });
});

it('returns an array of detected agents names for system discovery', function (): void {
    $mockJunie = Mockery::mock(Agent::class);
    $mockJunie->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(true);
    $mockJunie->shouldReceive('name')->andReturn('junie');

    $mockCursor = Mockery::mock(Agent::class);
    $mockCursor->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(true);
    $mockCursor->shouldReceive('name')->andReturn('cursor');

    $mockOther = Mockery::mock(Agent::class);
    $mockOther->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $mockOther->shouldReceive('name')->andReturn('other');

    $this->container->bind(Junie::class, fn () => $mockJunie);
    $this->container->bind(Cursor::class, fn () => $mockCursor);
    $this->container->bind(ClaudeCode::class, fn () => $mockOther);
    $this->container->bind(Codex::class, fn () => $mockOther);
    $this->container->bind(Copilot::class, fn () => $mockOther);
    $this->container->bind(OpenCode::class, fn () => $mockOther);
    $this->container->bind(Gemini::class, fn () => $mockOther);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverSystemInstalledAgents();

    expect($detected)->toBe(['junie', 'cursor']);
});

it('returns an empty array when no agents are detected for system discovery', function (): void {
    $mockAgent = Mockery::mock(Agent::class);
    $mockAgent->shouldReceive('detectOnSystem')->with(Mockery::type(Platform::class))->andReturn(false);
    $mockAgent->shouldReceive('name')->andReturn('mock');

    $this->container->bind(Junie::class, fn () => $mockAgent);
    $this->container->bind(Cursor::class, fn () => $mockAgent);
    $this->container->bind(ClaudeCode::class, fn () => $mockAgent);
    $this->container->bind(Codex::class, fn () => $mockAgent);
    $this->container->bind(Copilot::class, fn () => $mockAgent);
    $this->container->bind(OpenCode::class, fn () => $mockAgent);
    $this->container->bind(Gemini::class, fn () => $mockAgent);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverSystemInstalledAgents();

    expect($detected)->toBe([]);
});

it('returns an array of detected agent names for project discovery', function (): void {
    $basePath = '/test/project';

    $mockJunie = Mockery::mock(Agent::class);
    $mockJunie->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $mockJunie->shouldReceive('name')->andReturn('junie');

    $mockClaudeCode = Mockery::mock(Agent::class);
    $mockClaudeCode->shouldReceive('detectInProject')->with($basePath)->andReturn(true);
    $mockClaudeCode->shouldReceive('name')->andReturn('claude_code');

    $mockOther = Mockery::mock(Agent::class);
    $mockOther->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $mockOther->shouldReceive('name')->andReturn('other');

    $this->container->bind(Junie::class, fn () => $mockJunie);
    $this->container->bind(Cursor::class, fn () => $mockOther);
    $this->container->bind(ClaudeCode::class, fn () => $mockClaudeCode);
    $this->container->bind(Codex::class, fn () => $mockOther);
    $this->container->bind(Copilot::class, fn () => $mockOther);
    $this->container->bind(OpenCode::class, fn () => $mockOther);
    $this->container->bind(Gemini::class, fn () => $mockOther);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverProjectInstalledAgents($basePath);

    expect($detected)->toBe(['claude_code']);
});

it('returns an empty array when no agents are detected for project discovery', function (): void {
    $basePath = '/empty/project';

    $mockAgent = Mockery::mock(Agent::class);
    $mockAgent->shouldReceive('detectInProject')->with($basePath)->andReturn(false);
    $mockAgent->shouldReceive('name')->andReturn('mock');

    $this->container->bind(Junie::class, fn () => $mockAgent);
    $this->container->bind(Cursor::class, fn () => $mockAgent);
    $this->container->bind(ClaudeCode::class, fn () => $mockAgent);
    $this->container->bind(Codex::class, fn () => $mockAgent);
    $this->container->bind(Copilot::class, fn () => $mockAgent);
    $this->container->bind(OpenCode::class, fn () => $mockAgent);
    $this->container->bind(Gemini::class, fn () => $mockAgent);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverProjectInstalledAgents($basePath);

    expect($detected)->toBe([]);
});
