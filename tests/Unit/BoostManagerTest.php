<?php

declare(strict_types=1);

use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\Agents\ClaudeCode;
use Laravel\Boost\Install\Agents\Codex;
use Laravel\Boost\Install\Agents\Copilot;
use Laravel\Boost\Install\Agents\Cursor;
use Laravel\Boost\Install\Agents\Gemini;
use Laravel\Boost\Install\Agents\Junie;
use Laravel\Boost\Install\Agents\OpenCode;
use Tests\Unit\Install\ExampleAgent;

it('returns default agents', function (): void {
    $manager = new BoostManager;
    $registered = $manager->getAgents();

    expect($registered)->toMatchArray([
        'junie' => Junie::class,
        'cursor' => Cursor::class,
        'claudecode' => ClaudeCode::class,
        'codex' => Codex::class,
        'copilot' => Copilot::class,
        'opencode' => OpenCode::class,
        'gemini' => Gemini::class,
    ]);
});

it('can register a single agent', function (): void {
    $manager = new BoostManager;
    $manager->registerAgent('example', ExampleAgent::class);

    $registered = $manager->getAgents();

    expect($registered)->toHaveKey('example')
        ->and($registered['example'])->toBe(ExampleAgent::class)
        ->and($registered)->toHaveKey('junie');
});

it('can register multiple agents', function (): void {
    $manager = new BoostManager;
    $manager->registerAgent('example1', ExampleAgent::class);
    $manager->registerAgent('example2', ExampleAgent::class);

    $registered = $manager->getAgents();

    expect($registered)->toHaveKey('example1')->toHaveKey('example2')
        ->and($registered['example1'])->toBe(ExampleAgent::class)
        ->and($registered['example2'])->toBe(ExampleAgent::class)
        ->and($registered)->toHaveKey('junie');
});

it('throws an exception when registering a duplicate key', function (): void {
    $manager = new BoostManager;

    expect(fn () => $manager->registerAgent('junie', ExampleAgent::class))
        ->toThrow(InvalidArgumentException::class, "Agent 'junie' is already registered");
});

it('throws an exception when registering a custom agent with a duplicate key', function (): void {
    $manager = new BoostManager;
    $manager->registerAgent('custom', ExampleAgent::class);

    expect(fn () => $manager->registerAgent('custom', ExampleAgent::class))
        ->toThrow(InvalidArgumentException::class, "Agent 'custom' is already registered");
});
