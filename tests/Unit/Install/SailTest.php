<?php

declare(strict_types=1);

use Laravel\Boost\Install\Sail;

test('isActive returns true when LARAVEL_SAIL env var is set', function (): void {
    putenv('LARAVEL_SAIL=1');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(false);

    expect($sail->isActive())->toBeTrue();

    putenv('LARAVEL_SAIL=');
});

test('isActive returns false when running in devcontainer without LARAVEL_SAIL', function (): void {
    putenv('LARAVEL_SAIL=');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(true);

    expect($sail->isActive())->toBeFalse();
});

test('isActive returns true when LARAVEL_SAIL is set even inside a devcontainer', function (): void {
    putenv('LARAVEL_SAIL=1');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(true);

    expect($sail->isActive())->toBeTrue();

    putenv('LARAVEL_SAIL=');
});

test('isActive returns false when not sail user and no env var and not in container', function (): void {
    putenv('LARAVEL_SAIL=');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(false);

    // get_current_user() won't return 'sail' in the test environment
    expect($sail->isActive())->toBeFalse();
});
