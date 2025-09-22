<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Laravel\Boost\Install\Detection\CommandDetectionStrategy;
use Laravel\Boost\Install\Detection\CompositeDetectionStrategy;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Detection\DirectoryDetectionStrategy;
use Laravel\Boost\Install\Detection\FileDetectionStrategy;

beforeEach(function (): void {
    $this->container = new Container;
    $this->factory = new DetectionStrategyFactory($this->container);
});

test('creates directory strategy from string', function (): void {
    $strategy = $this->factory->make('directory');

    expect($strategy)->toBeInstanceOf(DirectoryDetectionStrategy::class);
});

test('creates file strategy from string', function (): void {
    $strategy = $this->factory->make('file');

    expect($strategy)->toBeInstanceOf(FileDetectionStrategy::class);
});

test('creates command strategy from string', function (): void {
    $strategy = $this->factory->make('command');

    expect($strategy)->toBeInstanceOf(CommandDetectionStrategy::class);
});

test('creates composite strategy from array of strings', function (): void {
    $strategy = $this->factory->make([
        'directory',
        'file',
    ]);

    expect($strategy)->toBeInstanceOf(CompositeDetectionStrategy::class);
});

test('creates composite strategy from mixed array', function (): void {
    $strategy = $this->factory->make([
        'directory',
        'file',
        'command',
    ]);

    expect($strategy)->toBeInstanceOf(CompositeDetectionStrategy::class);
});

test('throws exception for unknown string type', function (): void {
    expect(fn () => $this->factory->make('unknown'))
        ->toThrow(InvalidArgumentException::class);
});

test('empty array creates composite strategy', function (): void {
    $strategy = $this->factory->make([]);

    expect($strategy)->toBeInstanceOf(CompositeDetectionStrategy::class);
});

test('makeFromConfig infers directory type from paths key', function (): void {
    $strategy = $this->factory->makeFromConfig([
        'paths' => ['/some/path'],
    ]);

    expect($strategy)->toBeInstanceOf(DirectoryDetectionStrategy::class);
});

test('makeFromConfig infers file type from files key', function (): void {
    $strategy = $this->factory->makeFromConfig([
        'files' => ['file.txt'],
    ]);

    expect($strategy)->toBeInstanceOf(FileDetectionStrategy::class);
});

test('makeFromConfig infers command type from command key', function (): void {
    $strategy = $this->factory->makeFromConfig([
        'command' => 'which code',
    ]);

    expect($strategy)->toBeInstanceOf(CommandDetectionStrategy::class);
});

test('makeFromConfig creates composite strategy from multiple keys', function (): void {
    $strategy = $this->factory->makeFromConfig([
        'paths' => ['.claude'],
        'files' => ['CLAUDE.md'],
    ]);

    expect($strategy)->toBeInstanceOf(CompositeDetectionStrategy::class);
});

test('makeFromConfig throws exception for unknown config keys', function (): void {
    expect(fn () => $this->factory->makeFromConfig([
        'unknown_key' => 'value',
    ]))->toThrow(InvalidArgumentException::class, 'Cannot infer detection type from config keys');
});

test('makeFromConfig throws exception for empty config', function (): void {
    expect(fn () => $this->factory->makeFromConfig([]))
        ->toThrow(InvalidArgumentException::class, 'Cannot infer detection type from config keys');
});
