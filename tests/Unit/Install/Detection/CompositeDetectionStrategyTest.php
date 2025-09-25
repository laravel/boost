<?php

declare(strict_types=1);

use Laravel\Boost\Install\Contracts\DetectionStrategy;
use Laravel\Boost\Install\Detection\CompositeDetectionStrategy;
use Laravel\Boost\Install\Enums\Platform;

beforeEach(function (): void {
    $this->firstStrategy = Mockery::mock(DetectionStrategy::class);
    $this->secondStrategy = Mockery::mock(DetectionStrategy::class);
    $this->thirdStrategy = Mockery::mock(DetectionStrategy::class);
});

afterEach(function (): void {
    Mockery::close();
});

test('returns true when first strategy succeeds', function (): void {
    $this->firstStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['config' => 'value'], null)
        ->andReturn(true);

    $this->secondStrategy
        ->shouldNotReceive('detect');

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
    ]);

    $result = $composite->detect(['config' => 'value']);

    expect($result)->toBeTrue();
});

test('returns true when second strategy succeeds', function (): void {
    $this->firstStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['config' => 'value'], null)
        ->andReturn(false);

    $this->secondStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['config' => 'value'], null)
        ->andReturn(true);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
    ]);

    $result = $composite->detect(['config' => 'value']);

    expect($result)->toBeTrue();
});

test('returns false when all strategies fail', function (): void {
    $this->firstStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['config' => 'value'], Platform::Linux)
        ->andReturn(false);

    $this->secondStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['config' => 'value'], Platform::Linux)
        ->andReturn(false);

    $this->thirdStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['config' => 'value'], Platform::Linux)
        ->andReturn(false);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
        $this->thirdStrategy,
    ]);

    $result = $composite->detect(['config' => 'value'], Platform::Linux);

    expect($result)->toBeFalse();
});

test('stops execution after first success', function (): void {
    $this->firstStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['paths' => ['test']], Platform::Darwin)
        ->andReturn(false);

    $this->secondStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['paths' => ['test']], Platform::Darwin)
        ->andReturn(true);

    $this->thirdStrategy
        ->shouldNotReceive('detect');

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
        $this->thirdStrategy,
    ]);

    $result = $composite->detect(['paths' => ['test']], Platform::Darwin);

    expect($result)->toBeTrue();
});

test('handles empty strategies array', function (): void {
    $composite = new CompositeDetectionStrategy([]);

    $result = $composite->detect(['config' => 'value']);

    expect($result)->toBeFalse();
});

test('handles single strategy', function (): void {
    $this->firstStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['single' => 'test'], null)
        ->andReturn(true);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
    ]);

    $result = $composite->detect(['single' => 'test']);

    expect($result)->toBeTrue();
});

test('passes platform parameter to all strategies', function (): void {
    $this->firstStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['config' => 'test'], Platform::Windows)
        ->andReturn(false);

    $this->secondStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['config' => 'test'], Platform::Windows)
        ->andReturn(false);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
    ]);

    $result = $composite->detect(['config' => 'test'], Platform::Windows);

    expect($result)->toBeFalse();
});

test('handles null platform parameter', function (): void {
    $this->firstStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['config' => 'test'], null)
        ->andReturn(true);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
    ]);

    $result = $composite->detect(['config' => 'test'], null);

    expect($result)->toBeTrue();
});

test('handles mixed strategy types', function (): void {
    // This test simulates real-world usage where different strategy types
    // might be combined (directory, file, command, etc.)

    $this->firstStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['paths' => ['.vscode']], null)
        ->andReturn(false);

    $this->secondStrategy
        ->shouldReceive('detect')
        ->once()
        ->with(['paths' => ['.vscode']], null)
        ->andReturn(true);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
    ]);

    $result = $composite->detect(['paths' => ['.vscode']]);

    expect($result)->toBeTrue();
});
