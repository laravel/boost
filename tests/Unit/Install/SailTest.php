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

test('isActive returns false when LARAVEL_SAIL is set inside a devcontainer', function (): void {
    putenv('LARAVEL_SAIL=1');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(true);

    expect($sail->isActive())->toBeFalse();

    putenv('LARAVEL_SAIL=');
});

test('isActive returns false when not sail user and no env var and not in container', function (): void {
    putenv('LARAVEL_SAIL=');

    $sail = Mockery::mock(Sail::class)->makePartial();
    $sail->shouldReceive('isRunningInDevcontainer')->andReturn(false);

    // get_current_user() won't return 'sail' in the test environment
    expect($sail->isActive())->toBeFalse();
});

test('isRunningInDevcontainer returns true when REMOTE_CONTAINERS is true', function (): void {
    putenv('REMOTE_CONTAINERS=true');

    expect((new Sail)->isRunningInDevcontainer())->toBeTrue();

    putenv('REMOTE_CONTAINERS=');
});

test('isRunningInDevcontainer returns false when REMOTE_CONTAINERS is not set', function (): void {
    putenv('REMOTE_CONTAINERS=');

    expect((new Sail)->isRunningInDevcontainer())->toBeFalse();
});
