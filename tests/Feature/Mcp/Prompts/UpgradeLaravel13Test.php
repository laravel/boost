<?php

declare(strict_types=1);

use Laravel\Boost\Install\Herd;
use Laravel\Boost\Mcp\Prompts\UpgradeLaravel13\UpgradeLaravel13;

beforeEach(function (): void {
    $this->prompt = new UpgradeLaravel13;

    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('isInstalled')->andReturn(false)->byDefault();
    $this->app->instance(Herd::class, $herd);
});

test('it has the correct name', function (): void {
    expect($this->prompt->name())->toBe('upgrade-laravel-13');
});

test('it returns a valid response', function (): void {
    $response = $this->prompt->handle();

    expect($response)
        ->isToolResult()
        ->toolHasNoError();
});

test('it contains core upgrade content', function (): void {
    $response = $this->prompt->handle();

    expect($response)->isToolResult()
        ->toolTextContains('Laravel 12 to 13 Upgrade Specialist')
        ->toolTextContains('Request Forgery Protection')
        ->toolTextContains('PreventRequestForgery')
        ->toolTextContains('serializable_classes')
        ->toolTextContains('Cache Prefixes and Session Cookie Names')
        ->toolTextContains('JobAttempted')
        ->toolTextContains('QueueBusy');
});

test('it properly compiles blade assist helpers', function (): void {
    $response = $this->prompt->handle();
    $text = (string) $response->content();

    expect($text)
        ->toContain('composer show laravel/framework')
        ->toContain('composer require laravel/framework:^13.0 --with-all-dependencies')
        ->toContain('composer update')
        ->not->toContain('$assist->composerCommand')
        ->not->toContain('$assist->artisanCommand')
        ->not->toContain('{{ $assist')
        ->not->toContain('@if')
        ->not->toContain('@else')
        ->not->toContain('@endif');
});

test('it shows the composer installer command when herd is not installed', function (): void {
    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('composer global update laravel/installer')
        ->not->toContain('herd laravel:update');
});

test('it shows herd update command when herd is installed', function (): void {
    $herd = Mockery::mock(Herd::class);
    $herd->shouldReceive('isInstalled')->andReturn(true);
    $this->app->instance(Herd::class, $herd);

    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('herd laravel:update')
        ->not->toContain('composer global update laravel/installer');
});

test('it does not contain shift references', function (): void {
    $text = (string) $this->prompt->handle()->content();

    expect($text)->not->toContain('laravelshift.com');
});
