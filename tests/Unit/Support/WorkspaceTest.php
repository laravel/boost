<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Support\Workspace;
use Symfony\Component\Process\Process;

use function Orchestra\Testbench\default_skeleton_path;

it('uses artisan inside a Laravel application', function (): void {
    expect(Workspace::artisan())->toBe('artisan');
});

it('installs into the package when running through the Testbench CLI', function (): void {
    $package = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-testbench-'.uniqid();

    File::ensureDirectoryExists($package);
    File::put($package.'/composer.json', json_encode(['name' => 'acme/package']));
    File::put($package.'/boost.json', json_encode(['agents' => ['claude_code']]));
    File::link(realpath(__DIR__.'/../../../vendor'), $package.'/vendor');

    // The Testbench CLI writes into its skeleton application, so give it a private copy
    // rather than the shared one the rest of the test suite boots from.
    File::copyDirectory(default_skeleton_path(), $package.'/skeleton');
    File::delete([$package.'/skeleton/.env', ...File::glob($package.'/skeleton/bootstrap/cache/*.php')]);

    // Boost is the root package of the linked vendor directory, so it isn't auto-discovered.
    File::put($package.'/testbench.yaml', implode("\n", [
        'laravel: ./skeleton',
        'providers:',
        '  - Laravel\Boost\BoostServiceProvider',
        '',
    ]));

    try {
        $process = new Process(
            [PHP_BINARY, 'vendor/bin/testbench', 'boost:install', '--guidelines', '--mcp', '--no-interaction'],
            $package,
            ['TESTBENCH_WORKING_PATH' => $package, 'APP_ENV' => 'local'],
            timeout: 120,
        );

        $process->mustRun();

        $mcp = json_decode(File::get($package.'/.mcp.json'), true);

        expect($mcp['mcpServers']['laravel-boost'])->toBe([
            'command' => 'php',
            'args' => ['vendor/bin/testbench', 'boost:mcp'],
        ])
            ->and(File::get($package.'/AGENTS.md'))
            ->toContain('php vendor/bin/testbench')
            ->not->toContain('php artisan')
            ->and(json_decode(File::get($package.'/boost.json'), true))
            ->toMatchArray(['agents' => ['claude_code'], 'guidelines' => true, 'mcp' => true]);
    } finally {
        @unlink($package.'/vendor');
        File::deleteDirectory($package);
    }
})->skipOnWindows();
