<?php

declare(strict_types=1);

use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\Agents\ClaudeCode;
use Laravel\Boost\Install\Agents\Codex;
use Laravel\Boost\Install\Agents\Copilot;
use Laravel\Boost\Install\Agents\Cursor;
use Laravel\Boost\Install\Agents\PhpStorm;
use Laravel\Boost\Install\Agents\VSCode;
use Tests\Unit\Install\ExampleAgent;

it('returns default agents', function (): void {
    $manager = new BoostManager;
    $registered = $manager->getAgents();

    expect($registered)->toMatchArray([
        'phpstorm' => PhpStorm::class,
        'vscode' => VSCode::class,
        'cursor' => Cursor::class,
        'claude_code' => ClaudeCode::class,
        'codex' => Codex::class,
        'copilot' => Copilot::class,
    ]);
});

it('can register a single guideline', function (): void {
    $manager = new BoostManager;
    $manager->registerAgent('example', ExampleAgent::class);

    $registered = $manager->getAgents();

    expect($registered)->toHaveKey('example')
        ->and($registered['example'])->toBe(ExampleAgent::class)
        ->and($registered)->toHaveKey('phpstorm');
});

it('can register multiple custom agents', function (): void {
    $manager = new BoostManager;
    $manager->registerAgent('example1', ExampleAgent::class);
    $manager->registerAgent('example2', ExampleAgent::class);

    $registered = $manager->getAgents();

    expect($registered)->toHaveKey('example1')->toHaveKey('example2')
        ->and($registered['example1'])->toBe(ExampleAgent::class)
        ->and($registered['example2'])->toBe(ExampleAgent::class)
        ->and($registered)->toHaveKey('phpstorm');
});

it('throws an exception when registering a duplicate guideline', function (): void {
    $manager = new BoostManager;

    expect(fn () => $manager->registerAgent('phpstorm', ExampleAgent::class))
        ->toThrow(InvalidArgumentException::class, "Agent 'phpstorm' is already registered");
});

it('throws an exception when registering a custom guideline twice', function (): void {
    $manager = new BoostManager;
    $manager->registerAgent('custom', ExampleAgent::class);

    expect(fn () => $manager->registerAgent('custom', ExampleAgent::class))
        ->toThrow(InvalidArgumentException::class, "Agent 'custom' is already registered");
});
