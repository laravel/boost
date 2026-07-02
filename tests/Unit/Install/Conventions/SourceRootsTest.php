<?php

declare(strict_types=1);

use Laravel\Boost\Install\Conventions\SourceRoots;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->app->setBasePath(fixture('conventions/modular-app'));
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
});

it('resolves PSR-4 autoload roots including custom modular directories', function (): void {
    $roots = (new SourceRoots)->resolve();

    expect($roots)->toContain(base_path('app'));
    expect($roots)->toContain(base_path('src/Domain'));
});

it('only returns directories that exist', function (): void {
    $roots = (new SourceRoots)->resolve();

    foreach ($roots as $root) {
        expect(is_dir($root))->toBeTrue();
    }
});
