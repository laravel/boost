<?php

declare(strict_types=1);

use JMac\Testing\Double;
use Laravel\Boost\Install\Contracts\DetectionStrategy;
use Laravel\Boost\Install\Detection\CompositeDetectionStrategy;
use Laravel\Boost\Install\Enums\Platform;

beforeEach(function (): void {
    $this->firstStrategy = Double::for(DetectionStrategy::class);
    $this->secondStrategy = Double::for(DetectionStrategy::class);
    $this->thirdStrategy = Double::for(DetectionStrategy::class);
});

test('returns true when first strategy succeeds', function (): void {
    $this->firstStrategy->expects('detect')->with(['config' => 'value'], null)->returns(true);

    $this->secondStrategy->expects('detect')->never();

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
    ]);

    $result = $composite->detect(['config' => 'value']);

    expect($result)->toBeTrue();
});

test('returns true when second strategy succeeds', function (): void {
    $this->firstStrategy->expects('detect')->with(['config' => 'value'], null)->returns(false);

    $this->secondStrategy->expects('detect')->with(['config' => 'value'], null)->returns(true);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
    ]);

    $result = $composite->detect(['config' => 'value']);

    expect($result)->toBeTrue();
});

test('returns false when all strategies fail', function (): void {
    $this->firstStrategy->expects('detect')->with(['config' => 'value'], Platform::Linux)->returns(false);

    $this->secondStrategy->expects('detect')->with(['config' => 'value'], Platform::Linux)->returns(false);

    $this->thirdStrategy->expects('detect')->with(['config' => 'value'], Platform::Linux)->returns(false);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
        $this->thirdStrategy,
    ]);

    $result = $composite->detect(['config' => 'value'], Platform::Linux);

    expect($result)->toBeFalse();
});

test('stops execution after first success', function (): void {
    $this->firstStrategy->expects('detect')->with(['paths' => ['test']], Platform::Darwin)->returns(false);

    $this->secondStrategy->expects('detect')->with(['paths' => ['test']], Platform::Darwin)->returns(true);

    $this->thirdStrategy->expects('detect')->never();

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
    $this->firstStrategy->expects('detect')->with(['single' => 'test'], null)->returns(true);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
    ]);

    $result = $composite->detect(['single' => 'test']);

    expect($result)->toBeTrue();
});

test('passes platform parameter to all strategies', function (): void {
    $this->firstStrategy->expects('detect')->with(['config' => 'test'], Platform::Windows)->returns(false);

    $this->secondStrategy->expects('detect')->with(['config' => 'test'], Platform::Windows)->returns(false);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
    ]);

    $result = $composite->detect(['config' => 'test'], Platform::Windows);

    expect($result)->toBeFalse();
});

test('handles null platform parameter', function (): void {
    $this->firstStrategy->expects('detect')->with(['config' => 'test'], null)->returns(true);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
    ]);

    $result = $composite->detect(['config' => 'test']);

    expect($result)->toBeTrue();
});

test('handles mixed strategy types', function (): void {
    // This test simulates real-world usage where different strategy types
    // might be combined (directory, file, command, etc.)

    $this->firstStrategy->expects('detect')->with(['paths' => ['.vscode']], null)->returns(false);

    $this->secondStrategy->expects('detect')->with(['paths' => ['.vscode']], null)->returns(true);

    $composite = new CompositeDetectionStrategy([
        $this->firstStrategy,
        $this->secondStrategy,
    ]);

    $result = $composite->detect(['paths' => ['.vscode']]);

    expect($result)->toBeTrue();
});
