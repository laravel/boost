<?php

declare(strict_types=1);

use Laravel\Boost\BoostManager;
use Laravel\Boost\Install\CodeEnvironment\ClaudeCode;
use Laravel\Boost\Install\CodeEnvironment\Codex;
use Laravel\Boost\Install\CodeEnvironment\Copilot;
use Laravel\Boost\Install\CodeEnvironment\Cursor;
use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\CodeEnvironment\VSCode;
use Tests\Unit\Install\ExampleCodeEnvironment;

it('return default code environments', function (): void {
    $manager = new BoostManager;
    $registered = $manager->getCodeEnvironments();

    expect($registered)->toMatchArray([
        'phpstorm' => PhpStorm::class,
        'vscode' => VSCode::class,
        'cursor' => Cursor::class,
        'claudecode' => ClaudeCode::class,
        'codex' => Codex::class,
        'copilot' => Copilot::class,
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

it('does not overwrite default code environments', function (): void {
    $manager = new BoostManager;
    $manager->registerCodeEnvironment('phpstorm', PHPStorm::class);
    $manager->registerCodeEnvironment('phpstorm', ExampleCodeEnvironment::class);

    $registered = $manager->getCodeEnvironments();

    expect($registered)->toHaveKey('phpstorm')
        ->and($registered['phpstorm'])->toBe(PHPStorm::class)
        ->and($registered['phpstorm'])->not()->toBe(ExampleCodeEnvironment::class);
});
