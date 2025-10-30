<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\Herd;
use Laravel\Roster\Roster;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Orchestra\Testbench\Foundation\Config as TestbenchConfig;

// Bootstrap the Laravel application using Testbench
$app = Testbench::createFromConfig(new TestbenchConfig([
    'env' => [
        'APP_URL=http://localhost.test',
    ],
]), options: ['enables_package_discoveries' => false]);

// Create a mock Roster that returns ALL packages from .ai/ directory
$mockRoster = new class extends Roster
{
    public function packages(): \Laravel\Roster\PackageCollection
    {
        $packages = [];

        // Find all package directories in .ai/
        $directories = glob(__DIR__.'/.ai/*', GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $packageName = basename($dir);

            // Skip special directories handled elsewhere in GuidelineComposer
            if (in_array($packageName, ['boost', 'herd'], true)) {
                continue;
            }

            // Map directory names to Roster enum values where they exist
            $enumMapping = [
                'php' => \Laravel\Roster\Enums\Packages::LARAVEL, // Use Laravel as placeholder for php
                'laravel' => \Laravel\Roster\Enums\Packages::LARAVEL,
                'filament' => \Laravel\Roster\Enums\Packages::FILAMENT,
                'fluxui-free' => \Laravel\Roster\Enums\Packages::FLUXUI_FREE,
                'fluxui-pro' => \Laravel\Roster\Enums\Packages::FLUXUI_PRO,
                'inertia-laravel' => \Laravel\Roster\Enums\Packages::INERTIA_LARAVEL,
                'inertia-react' => \Laravel\Roster\Enums\Packages::INERTIA_REACT,
                'inertia-vue' => \Laravel\Roster\Enums\Packages::INERTIA_VUE,
                'livewire' => \Laravel\Roster\Enums\Packages::LIVEWIRE,
                'pest' => \Laravel\Roster\Enums\Packages::PEST,
                'phpunit' => \Laravel\Roster\Enums\Packages::PHPUNIT,
                'pint' => \Laravel\Roster\Enums\Packages::PINT,
                'volt' => \Laravel\Roster\Enums\Packages::VOLT,
                'folio' => \Laravel\Roster\Enums\Packages::FOLIO,
                'pennant' => \Laravel\Roster\Enums\Packages::PENNANT,
                'tailwindcss' => \Laravel\Roster\Enums\Packages::TAILWINDCSS,
                'wayfinder' => \Laravel\Roster\Enums\Packages::WAYFINDER,
            ];

            if (isset($enumMapping[$packageName])) {
                // Find ALL version directories and create a package for each
                $versionDirs = glob(__DIR__."/.ai/{$packageName}/*", GLOB_ONLYDIR);
                if (! empty($versionDirs)) {
                    $versions = array_map('basename', $versionDirs);
                    sort($versions, SORT_NUMERIC);

                    // Create a package instance for each version found
                    foreach ($versions as $versionNumber) {
                        $packages[] = new \Laravel\Roster\Package(
                            $enumMapping[$packageName],
                            $packageName,
                            $versionNumber.'.0.0',
                            false
                        );
                    }
                } else {
                    // No version directories, just add the core package
                    $packages[] = new \Laravel\Roster\Package(
                        $enumMapping[$packageName],
                        $packageName,
                        '1.0.0',
                        false
                    );
                }
            }
        }

        return new \Laravel\Roster\PackageCollection($packages);
    }
};

$herd = new Herd;

// Create GuidelineComposer with all config options enabled to get ALL guidelines
$config = new GuidelineConfig;
$config->laravelStyle = true;
$config->hasAnApi = true;
$config->caresAboutLocalization = true;
$config->enforceTests = true;

// Use the real GuidelineComposer with our mock Roster - this will use the exact same ordering logic
$composer = new GuidelineComposer($mockRoster, $herd);
$composer->config($config);

// Get the guidelines that GuidelineComposer would normally find
$guidelines = $composer->guidelines();

// Add missing PHP versions (since GuidelineComposer only adds current PHP version)
$reflection = new ReflectionClass($composer);
$guidelineDirMethod = $reflection->getMethod('guidelinesDir');
$guidelineDirMethod->setAccessible(true);

$phpVersions = ['8.1', '8.2', '8.3', '8.4'];
$currentPhp = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

foreach ($phpVersions as $phpVersion) {
    if ($phpVersion !== $currentPhp) {
        $content = $guidelineDirMethod->invoke($composer, "php/{$phpVersion}");
        if (! empty($content)) {
            $guidelines->put("php/v{$phpVersion}", $content);
        }
    }
}

// Now compose ALL guidelines (original + missing PHP versions)
echo GuidelineComposer::composeGuidelines($guidelines);
