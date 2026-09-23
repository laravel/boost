<?php

declare(strict_types=1);

use JMac\Testing\Double;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\Agents\Agent;
use Laravel\Boost\Install\Agents\Amp;
use Laravel\Boost\Install\Agents\Antigravity;
use Laravel\Boost\Install\Agents\ClaudeCode;
use Laravel\Boost\Install\Agents\Codex;
use Laravel\Boost\Install\Agents\Copilot;
use Laravel\Boost\Install\Agents\Cursor;
use Laravel\Boost\Install\Agents\Factory;
use Laravel\Boost\Install\Agents\GrokBuild;
use Laravel\Boost\Install\Agents\Junie;
use Laravel\Boost\Install\Agents\Kiro;
use Laravel\Boost\Install\Agents\OpenCode;
use Laravel\Boost\Install\Agents\Pi;
use Laravel\Boost\Install\Agents\Zed;
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
        ->and($agents->count())->toBe(13)
        ->and($agents->keys()->toArray())->toBe([
            'amp', 'antigravity', 'claude_code', 'codex', 'copilot', 'cursor', 'factory', 'grok_build', 'junie', 'kiro', 'opencode', 'pi', 'zed',
        ]);

    $agents->each(function ($agent): void {
        expect($agent)->toBeInstanceOf(Agent::class);
    });
});

it('returns an array of detected agents names for system discovery', function (): void {
    $mockJunie = Double::for(Agent::class);
    $mockJunie->allows('detectOnSystem')->with(Mockery::type(Platform::class))->returns(true);
    $mockJunie->allows('name')->returns('junie');

    $mockCursor = Double::for(Agent::class);
    $mockCursor->allows('detectOnSystem')->with(Mockery::type(Platform::class))->returns(true);
    $mockCursor->allows('name')->returns('cursor');

    $mockOther = Double::for(Agent::class);
    $mockOther->allows('detectOnSystem')->with(Mockery::type(Platform::class))->returns(false);
    $mockOther->allows('name')->returns('other');

    $this->container->bind(Amp::class, fn () => $mockOther);
    $this->container->bind(Junie::class, fn () => $mockJunie);
    $this->container->bind(Cursor::class, fn () => $mockCursor);
    $this->container->bind(ClaudeCode::class, fn () => $mockOther);
    $this->container->bind(Codex::class, fn () => $mockOther);
    $this->container->bind(Copilot::class, fn () => $mockOther);
    $this->container->bind(Factory::class, fn () => $mockOther);
    $this->container->bind(Kiro::class, fn () => $mockOther);
    $this->container->bind(OpenCode::class, fn () => $mockOther);
    $this->container->bind(Antigravity::class, fn () => $mockOther);
    $this->container->bind(Zed::class, fn () => $mockOther);
    $this->container->bind(Pi::class, fn () => $mockOther);
    $this->container->bind(GrokBuild::class, fn () => $mockOther);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverSystemInstalledAgents();

    expect($detected)->toBe(['cursor', 'junie']);
});

it('returns an empty array when no agents are detected for system discovery', function (): void {
    $mockAgent = Double::for(Agent::class);
    $mockAgent->allows('detectOnSystem')->with(Mockery::type(Platform::class))->returns(false);
    $mockAgent->allows('name')->returns('mock');

    $this->container->bind(Amp::class, fn () => $mockAgent);
    $this->container->bind(Junie::class, fn () => $mockAgent);
    $this->container->bind(Cursor::class, fn () => $mockAgent);
    $this->container->bind(ClaudeCode::class, fn () => $mockAgent);
    $this->container->bind(Codex::class, fn () => $mockAgent);
    $this->container->bind(Copilot::class, fn () => $mockAgent);
    $this->container->bind(Factory::class, fn () => $mockAgent);
    $this->container->bind(Kiro::class, fn () => $mockAgent);
    $this->container->bind(OpenCode::class, fn () => $mockAgent);
    $this->container->bind(Antigravity::class, fn () => $mockAgent);
    $this->container->bind(Zed::class, fn () => $mockAgent);
    $this->container->bind(Pi::class, fn () => $mockAgent);
    $this->container->bind(GrokBuild::class, fn () => $mockAgent);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverSystemInstalledAgents();

    expect($detected)->toBe([]);
});

it('returns an array of detected agent names for project discovery', function (): void {
    $basePath = '/test/project';

    $mockJunie = Double::for(Agent::class);
    $mockJunie->allows('detectInProject')->with($basePath)->returns(false);
    $mockJunie->allows('name')->returns('junie');

    $mockClaudeCode = Double::for(Agent::class);
    $mockClaudeCode->allows('detectInProject')->with($basePath)->returns(true);
    $mockClaudeCode->allows('name')->returns('claude_code');

    $mockOther = Double::for(Agent::class);
    $mockOther->allows('detectInProject')->with($basePath)->returns(false);
    $mockOther->allows('name')->returns('other');

    $this->container->bind(Amp::class, fn () => $mockOther);
    $this->container->bind(Junie::class, fn () => $mockJunie);
    $this->container->bind(Cursor::class, fn () => $mockOther);
    $this->container->bind(ClaudeCode::class, fn () => $mockClaudeCode);
    $this->container->bind(Codex::class, fn () => $mockOther);
    $this->container->bind(Copilot::class, fn () => $mockOther);
    $this->container->bind(Factory::class, fn () => $mockOther);
    $this->container->bind(Kiro::class, fn () => $mockOther);
    $this->container->bind(OpenCode::class, fn () => $mockOther);
    $this->container->bind(Antigravity::class, fn () => $mockOther);
    $this->container->bind(Zed::class, fn () => $mockOther);
    $this->container->bind(Pi::class, fn () => $mockOther);
    $this->container->bind(GrokBuild::class, fn () => $mockOther);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverProjectInstalledAgents($basePath);

    expect($detected)->toBe(['claude_code']);
});

it('returns an empty array when no agents are detected for project discovery', function (): void {
    $basePath = '/empty/project';

    $mockAgent = Double::for(Agent::class);
    $mockAgent->allows('detectInProject')->with($basePath)->returns(false);
    $mockAgent->allows('name')->returns('mock');

    $this->container->bind(Amp::class, fn () => $mockAgent);
    $this->container->bind(Junie::class, fn () => $mockAgent);
    $this->container->bind(Cursor::class, fn () => $mockAgent);
    $this->container->bind(ClaudeCode::class, fn () => $mockAgent);
    $this->container->bind(Codex::class, fn () => $mockAgent);
    $this->container->bind(Copilot::class, fn () => $mockAgent);
    $this->container->bind(Factory::class, fn () => $mockAgent);
    $this->container->bind(Kiro::class, fn () => $mockAgent);
    $this->container->bind(OpenCode::class, fn () => $mockAgent);
    $this->container->bind(Antigravity::class, fn () => $mockAgent);
    $this->container->bind(Zed::class, fn () => $mockAgent);
    $this->container->bind(Pi::class, fn () => $mockAgent);
    $this->container->bind(GrokBuild::class, fn () => $mockAgent);

    $detector = new AgentsDetector($this->container, $this->boostManager);
    $detected = $detector->discoverProjectInstalledAgents($basePath);

    expect($detected)->toBe([]);
});
