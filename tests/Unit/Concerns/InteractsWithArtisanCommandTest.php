<?php

declare(strict_types=1);

use Laravel\Boost\Concerns\InteractsWithArtisanCommand;

beforeEach(function (): void {
    $this->trait = new class
    {
        use InteractsWithArtisanCommand;

        public function testCallArtisan(string $command, array $options = []): string
        {
            return $this->callArtisanCommand($command, $options);
        }
    };
});

it('executes simple artisan command successfully', function (): void {
    $output = $this->trait->testCallArtisan('list');

    expect($output)
        ->toBeString()
        ->toContain('Available commands');
});

it('captures output correctly', function (): void {
    $output = $this->trait->testCallArtisan('list');

    expect($output)
        ->toBeString()
        ->not->toBeEmpty();
});

it('passes options correctly', function (): void {
    $output = $this->trait->testCallArtisan('list', ['--format' => 'json']);

    expect($output)->toBeString();
});

it('returns trimmed output', function (): void {
    $output = $this->trait->testCallArtisan('list');

    expect($output)
        ->toBe(trim((string) $output))
        ->not->toStartWith(' ')
        ->not->toStartWith("\n")
        ->not->toEndWith(' ')
        ->not->toEndWith("\n");
});

it('throws exception on command failure', function (): void {
    expect(fn () => $this->trait->testCallArtisan('nonexistent:command'))
        ->toThrow(RuntimeException::class);
});

it('includes command name in exception message', function (): void {
    try {
        $this->trait->testCallArtisan('nonexistent:command');
        expect(false)->toBeTrue(); // Should not reach here
    } catch (RuntimeException $runtimeException) {
        expect($runtimeException->getMessage())
            ->toContain('nonexistent:command')
            ->toContain('failed');
    }
});
