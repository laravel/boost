<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use Laravel\Boost\Install\Detection\CommandDetectionStrategy;
use Laravel\Boost\Install\Enums\Platform;

beforeEach(function (): void {
    $this->strategy = new CommandDetectionStrategy;
});

test('detects command with successful exit code', function (): void {
    Process::fake([
        'which php' => Process::result(exitCode: 0),
    ]);

    $result = $this->strategy->detect([
        'command' => 'which php',
    ]);

    expect($result)->toBeTrue();
})->skip();

test('fails for command with non zero exit code', function (): void {
    Process::fake([
        'which nonexistent' => Process::result(exitCode: 1),
    ]);

    $result = $this->strategy->detect([
        'command' => 'which nonexistent',
    ]);

    expect($result)->toBeFalse();
})->skip();

test('returns false when no command config', function (): void {
    $result = $this->strategy->detect([
        'other_config' => 'value',
    ]);

    expect($result)->toBeFalse();
})->skip();

test('handles command with output', function (): void {
    Process::fake([
        'echo test' => Process::result(output: 'test', exitCode: 0),
    ]);

    $result = $this->strategy->detect([
        'command' => 'echo test',
    ]);

    expect($result)->toBeTrue();
})->skip();

test('handles command with error output', function (): void {
    Process::fake([
        'invalid-command' => Process::result(errorOutput: 'command not found', exitCode: 127),
    ]);

    $result = $this->strategy->detect([
        'command' => 'invalid-command',
    ]);

    expect($result)->toBeFalse();
})->skip();

test('works with different platforms parameter', function (): void {
    Process::fake([
        'where code' => Process::result(exitCode: 0),
    ]);

    $result = $this->strategy->detect([
        'command' => 'where code',
    ], Platform::Windows);

    expect($result)->toBeTrue();
})->skip();
