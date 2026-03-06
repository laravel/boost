<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Mcp\Prompts\UpgradeInertiav3\UpgradeInertiaV3;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->prompt = new UpgradeInertiaV3;
});

function mockRosterWithFrameworks(bool $react = false, bool $vue = false, bool $svelte = false): Roster
{
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('uses')->with(Packages::INERTIA_REACT)->andReturn($react);
    $roster->shouldReceive('uses')->with(Packages::INERTIA_VUE)->andReturn($vue);
    $roster->shouldReceive('uses')->with(Packages::INERTIA_SVELTE)->andReturn($svelte);
    $roster->shouldReceive('uses')->with(Packages::INERTIA_LARAVEL)->andReturn(true);
    $roster->shouldReceive('usesVersion')->andReturn(false);
    $roster->shouldReceive('packages')->andReturn(collect());
    $roster->shouldReceive('nodePackageManager')->andReturn(null);

    return $roster;
}

test('it has the correct name', function (): void {
    expect($this->prompt->name())->toBe('upgrade-inertia-v3');
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
        ->toolTextContains('Inertia v2 to v3 Upgrade Specialist')
        ->toolTextContains('Axios removed')
        ->toolTextContains('qs removed')
        ->toolTextContains('Event renames')
        ->toolTextContains('`LazyProp` removed')
        ->toolTextContains('Config restructuring');
});

test('it properly compiles blade assist helpers', function (): void {
    $response = $this->prompt->handle();
    $text = (string) $response->content();

    expect($text)
        ->toContain('composer require inertiajs/inertia-laravel')
        ->toContain('composer show inertiajs/inertia-laravel')
        ->toContain('php artisan optimize:clear')
        ->toContain('php artisan vendor:publish --tag=inertia-config --force')
        ->toContain('php artisan view:clear')
        ->not->toContain('$assist->composerCommand')
        ->not->toContain('$assist->artisanCommand')
        ->not->toContain('$assist->nodePackageManagerCommand')
        ->not->toContain('{{ $assist');
});

test('it shows react-specific content when react adapter is installed', function (): void {
    $assist = app(GuidelineAssist::class, ['roster' => mockRosterWithFrameworks(react: true)]);
    $this->app->instance(GuidelineAssist::class, $assist);

    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('@inertiajs/react@^3.0')
        ->toContain('React 19+')
        ->toContain('React Setup')
        ->not->toContain('@inertiajs/vue3@^3.0')
        ->not->toContain('@inertiajs/svelte@^3.0')
        ->not->toContain('Vue Setup')
        ->not->toContain('Svelte Setup');
});

test('it shows vue-specific content when vue adapter is installed', function (): void {
    $assist = app(GuidelineAssist::class, ['roster' => mockRosterWithFrameworks(vue: true)]);
    $this->app->instance(GuidelineAssist::class, $assist);

    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('@inertiajs/vue3@^3.0')
        ->toContain('Vue 3.x')
        ->toContain('Vue Setup')
        ->not->toContain('@inertiajs/react@^3.0')
        ->not->toContain('@inertiajs/svelte@^3.0')
        ->not->toContain('React Setup')
        ->not->toContain('Svelte Setup');
});

test('it shows svelte-specific content when svelte adapter is installed', function (): void {
    $assist = app(GuidelineAssist::class, ['roster' => mockRosterWithFrameworks(svelte: true)]);
    $this->app->instance(GuidelineAssist::class, $assist);

    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('@inertiajs/svelte@^3.0')
        ->toContain('Svelte 5+')
        ->toContain('Svelte Setup')
        ->not->toContain('@inertiajs/react@^3.0')
        ->not->toContain('@inertiajs/vue3@^3.0')
        ->not->toContain('React Setup')
        ->not->toContain('Vue Setup');
});
