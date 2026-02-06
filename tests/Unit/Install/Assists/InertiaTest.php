<?php

declare(strict_types=1);

use Laravel\Boost\Install\Assists\Inertia;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->roster->shouldReceive('usesVersion')->andReturn(false);

    $this->inertia = new Inertia($this->roster);
});

afterEach(function (): void {
    $jsPath = base_path('resources/js');

    if (is_dir($jsPath.'/pages')) {
        rmdir($jsPath.'/pages');
    }

    if (is_dir($jsPath.'/Pages')) {
        rmdir($jsPath.'/Pages');
    }

    if (is_dir($jsPath)) {
        rmdir($jsPath);
    }

    if (is_dir(base_path('resources'))) {
        @rmdir(base_path('resources'));
    }
});

it('returns PascalCase Pages directory as default when no resources/js directory exists', function (): void {
    expect($this->inertia->pagesDirectory())->toBe('resources/js/Pages');
});

it('returns lowercase pages directory when it exists on disk', function (): void {
    $jsPath = base_path('resources/js');
    mkdir($jsPath, 0755, true);
    mkdir($jsPath.'/pages', 0755);

    expect($this->inertia->pagesDirectory())->toBe('resources/js/pages');
    expect($this->inertia->pagesDirectory())->not->toBe('resources/js/Pages');
});

it('returns PascalCase Pages directory when it exists on disk', function (): void {
    $jsPath = base_path('resources/js');
    mkdir($jsPath, 0755, true);
    mkdir($jsPath.'/Pages', 0755);

    expect($this->inertia->pagesDirectory())->toBe('resources/js/Pages');
    expect($this->inertia->pagesDirectory())->not->toBe('resources/js/pages');
});
