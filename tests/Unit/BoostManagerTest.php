<?php

declare(strict_types=1);

use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\CodeEnvironment\ClaudeCode;
use Laravel\Boost\Install\CodeEnvironment\Codex;
use Laravel\Boost\Install\CodeEnvironment\Copilot;
use Laravel\Boost\Install\CodeEnvironment\Cursor;
use Laravel\Boost\Install\CodeEnvironment\Gemini;
use Laravel\Boost\Install\CodeEnvironment\OpenCode;
use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\CodeEnvironment\VSCode;
use Tests\Unit\Install\ExampleCodeEnvironment;

it('returns default code environments', function (): void {
    $manager = new BoostManager;
    $registered = $manager->getCodeEnvironments();

    expect($registered)->toMatchArray([
        'phpstorm' => PhpStorm::class,
        'vscode' => VSCode::class,
        'cursor' => Cursor::class,
        'claudecode' => ClaudeCode::class,
        'codex' => Codex::class,
        'copilot' => Copilot::class,
        'opencode' => OpenCode::class,
        'gemini' => Gemini::class,
    ]);
});

it('can register a single code environment', function (): void {
    $manager = new BoostManager;
    $manager->registerCodeEnvironment('example', ExampleCodeEnvironment::class);

    $registered = $manager->getCodeEnvironments();

    expect($registered)->toHaveKey('example')
        ->and($registered['example'])->toBe(ExampleCodeEnvironment::class)
        ->and($registered)->toHaveKey('phpstorm');
});

it('can register multiple code environments', function (): void {
    $manager = new BoostManager;
    $manager->registerCodeEnvironment('example1', ExampleCodeEnvironment::class);
    $manager->registerCodeEnvironment('example2', ExampleCodeEnvironment::class);

    $registered = $manager->getCodeEnvironments();

    expect($registered)->toHaveKey('example1')->toHaveKey('example2')
        ->and($registered['example1'])->toBe(ExampleCodeEnvironment::class)
        ->and($registered['example2'])->toBe(ExampleCodeEnvironment::class)
        ->and($registered)->toHaveKey('phpstorm');
});

it('throws an exception when registering a duplicate key', function (): void {
    $manager = new BoostManager;

    expect(fn () => $manager->registerCodeEnvironment('phpstorm', ExampleCodeEnvironment::class))
        ->toThrow(InvalidArgumentException::class, "Code environment 'phpstorm' is already registered");
});

it('throws exception when registering custom environment with a duplicate key', function (): void {
    $manager = new BoostManager;
    $manager->registerCodeEnvironment('custom', ExampleCodeEnvironment::class);

    expect(fn () => $manager->registerCodeEnvironment('custom', ExampleCodeEnvironment::class))
        ->toThrow(InvalidArgumentException::class, "Code environment 'custom' is already registered");
});
