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
    $roster->shouldReceive('uses')->withAnyArgs()->andReturn(false)->byDefault();
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
        ->toolTextContains('`qs` dependency removed')
        ->toolTextContains('Event renames')
        ->toolTextContains('`LazyProp` removed')
        ->toolTextContains('Config restructuring')
        ->toolTextContains('router.cancelAll()')
        ->toolTextContains('hideProgress()');
});

test('it properly compiles blade assist helpers', function (): void {
    $response = $this->prompt->handle();
    $text = (string) $response->content();

    expect($text)
        ->toContain('composer require inertiajs/inertia-laravel:^3.0')
        ->toContain('composer show inertiajs/inertia-laravel')
        ->toContain('npm install @inertiajs/vite@^3.0')
        ->toContain('php artisan vendor:publish --provider=')
        ->toContain('php artisan view:clear')
        ->not->toContain('$assist->composerCommand')
        ->not->toContain('$assist->artisanCommand')
        ->not->toContain('$assist->nodePackageManagerCommand')
        ->not->toContain('{{ $assist')
        ->not->toContain('@if(')
        ->not->toContain('@endif')
        ->not->toContain('--tag=inertia-config');
});

test('it avoids the outdated migration guidance from the original draft', function (): void {
    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->not->toContain('replace with `fetch`')
        ->not->toContain('Use the native `URLSearchParams` API instead')
        ->not->toContain('onBefore')
        ->not->toContain('onStart')
        ->not->toContain('partialComponent')
        ->not->toContain('defaultComponent')
        ->not->toContain('Inertia::testing()')
        ->not->toContain('children render immediately; props may be undefined initially')
        ->not->toContain('React Setup')
        ->not->toContain('Vue Setup')
        ->not->toContain('Svelte Setup');
});

test('it shows react-specific content when react adapter is installed', function (): void {
    $assist = app(GuidelineAssist::class, ['roster' => mockRosterWithFrameworks(react: true)]);
    $this->app->instance(GuidelineAssist::class, $assist);

    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('@inertiajs/react@^3.0')
        ->toContain('React 19+')
        ->toContain('Deferred` component behavior (React)')
        ->toContain("import { progress } from '@inertiajs/react'")
        ->not->toContain('@inertiajs/vue3@^3.0')
        ->not->toContain('@inertiajs/svelte@^3.0')
        ->not->toContain('Svelte 5 runes syntax');
});

test('it shows vue-specific content when vue adapter is installed', function (): void {
    $assist = app(GuidelineAssist::class, ['roster' => mockRosterWithFrameworks(vue: true)]);
    $this->app->instance(GuidelineAssist::class, $assist);

    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('@inertiajs/vue3@^3.0')
        ->toContain("import { progress } from '@inertiajs/vue3'")
        ->not->toContain('@inertiajs/react@^3.0')
        ->not->toContain('@inertiajs/svelte@^3.0')
        ->not->toContain('Deferred` component behavior (React)')
        ->not->toContain('Svelte 5 runes syntax');
});

test('it shows svelte-specific content when svelte adapter is installed', function (): void {
    $assist = app(GuidelineAssist::class, ['roster' => mockRosterWithFrameworks(svelte: true)]);
    $this->app->instance(GuidelineAssist::class, $assist);

    $text = (string) $this->prompt->handle()->content();

    expect($text)
        ->toContain('@inertiajs/svelte@^3.0')
        ->toContain('Svelte 5+')
        ->toContain('Svelte 5 runes syntax')
        ->toContain("import { progress } from '@inertiajs/svelte'")
        ->not->toContain('@inertiajs/react@^3.0')
        ->not->toContain('@inertiajs/vue3@^3.0')
        ->not->toContain('Deferred` component behavior (React)');
});
