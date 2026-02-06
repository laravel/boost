<?php

declare(strict_types=1);

namespace Laravel\Boost\DocsIndex;

class DocsRegistry
{
    /**
     * @return array<string, array{repo: string, branch: string, version_from: string, path: string|list<string>}>
     */
    public static function repos(): array
    {
        return [
            'laravel/docs' => [
                'repo' => 'laravel/docs',
                'branch' => '{major}.x',
                'version_from' => 'laravel/framework',
                'path' => '/',
            ],
            'filamentphp/filament' => [
                'repo' => 'filamentphp/filament',
                'branch' => '{major}.x',
                'version_from' => 'filament/filament',
                'path' => [
                    'docs/',
                    'packages/actions/docs/',
                    'packages/forms/docs/',
                    'packages/infolists/docs/',
                    'packages/notifications/docs/',
                    'packages/schemas/docs/',
                    'packages/tables/docs/',
                    'packages/widgets/docs/',
                    'packages/support/docs/',
                ],
            ],
            'livewire/livewire' => [
                'repo' => 'livewire/livewire',
                'branch' => 'main',
                'version_from' => 'livewire/livewire',
                'path' => 'docs/',
            ],
            'pestphp/pest' => [
                'repo' => 'pestphp/docs',
                'branch' => '{major}.x',
                'version_from' => 'pestphp/pest',
                'path' => '/',
            ],
        ];
    }

    /**
     * @return array<string, array{package: string, source: string}>
     */
    public static function symlinks(): array
    {
        return [
            'filament-blueprint' => [
                'package' => 'filament/blueprint',
                'source' => 'vendor/filament/blueprint/resources/markdown/planning',
            ],
        ];
    }

    /**
     * Filter repos to only those whose version_from package is installed.
     *
     * @param  array<string, string>  $installedPackages  package name => version
     * @return array<string, array{repo: string, branch: string, version_from: string, path: string|list<string>}>
     */
    public static function installedRepos(array $installedPackages): array
    {
        return array_filter(
            self::repos(),
            fn (array $config): bool => isset($installedPackages[$config['version_from']]),
        );
    }
}
