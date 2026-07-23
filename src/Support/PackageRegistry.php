<?php

declare(strict_types=1);

namespace Laravel\Boost\Support;

class PackageRegistry
{
    public const BOOST = 'laravel/boost';

    public const FLUXUI_FREE = 'livewire/flux';

    public const FLUXUI_PRO = 'livewire/flux-pro';

    public const INERTIA_LARAVEL = 'inertiajs/inertia-laravel';

    public const INERTIA_REACT = '@inertiajs/react';

    public const INERTIA_SVELTE = '@inertiajs/svelte';

    public const INERTIA_VUE = '@inertiajs/vue3';

    public const LARAVEL = 'laravel/framework';

    public const LIVEWIRE = 'livewire/livewire';

    public const MCP = 'laravel/mcp';

    public const PEST = 'pestphp/pest';

    public const PHPUNIT = 'phpunit/phpunit';

    public const PINT = 'laravel/pint';

    public const SAIL = 'laravel/sail';

    /** @var array<string, string> */
    private const GUIDELINE_NAMES = [
        '@inertiajs/react' => 'inertia-react',
        '@inertiajs/svelte' => 'inertia-svelte',
        '@inertiajs/vue3' => 'inertia-vue',
        'inertiajs/inertia-laravel' => 'inertia-laravel',
        'laravel/boost' => 'boost',
        'laravel/folio' => 'folio',
        'laravel/framework' => 'laravel',
        'laravel/mcp' => 'mcp',
        'laravel/pennant' => 'pennant',
        'laravel/pint' => 'pint',
        'laravel/sail' => 'sail',
        'laravel/wayfinder' => 'wayfinder',
        'livewire/flux' => 'fluxui-free',
        'livewire/flux-pro' => 'fluxui-pro',
        'livewire/livewire' => 'livewire',
        'livewire/volt' => 'volt',
        'pestphp/pest' => 'pest',
        'phpunit/phpunit' => 'phpunit',
        'tailwindcss' => 'tailwindcss',
    ];

    public static function guidelineName(string $package): string
    {
        return self::GUIDELINE_NAMES[$package]
            ?? str_replace(['@', '/', '_'], ['', '-', '-'], strtolower($package));
    }

    public static function rosterName(string $package): string
    {
        return strtoupper(str_replace('-', '_', self::guidelineName($package)));
    }
}
