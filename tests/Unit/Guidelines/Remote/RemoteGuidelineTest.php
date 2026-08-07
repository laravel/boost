<?php

declare(strict_types=1);

use Laravel\Boost\Guidelines\Remote\RemoteGuideline;

it('describes a remote guideline and its preserved relative path', function (): void {
    $guideline = new RemoteGuideline(
        name: 'laravel/core',
        repo: 'owner/repo',
        path: 'guidelines/laravel/core.md',
        relativePath: 'laravel/core.md',
    );

    expect($guideline->name)->toBe('laravel/core')
        ->and($guideline->repo)->toBe('owner/repo')
        ->and($guideline->path)->toBe('guidelines/laravel/core.md')
        ->and($guideline->relativePath)->toBe('laravel/core.md');
});
