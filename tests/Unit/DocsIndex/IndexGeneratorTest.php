<?php

use Laravel\Boost\DocsIndex\IndexGenerator;

beforeEach(function (): void {
    $this->docsDir = 'test-docs-'.uniqid();
    $this->basePath = base_path($this->docsDir);
    @mkdir($this->basePath.'/laravel-docs', 0755, true);
    @mkdir($this->basePath.'/pestphp-docs', 0755, true);
});

afterEach(function (): void {
    // Clean up temp directory
    if (is_dir($this->basePath)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->basePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
        }

        @rmdir($this->basePath);
    }
});

it('generates correct pipe-delimited format', function (): void {
    file_put_contents($this->basePath.'/laravel-docs/routing.md', '# Routing');
    file_put_contents($this->basePath.'/laravel-docs/cache.md', '# Cache');

    $generator = new IndexGenerator;
    $index = $generator->generate($this->docsDir);

    expect($index)
        ->toContain('[Laravel Docs Index]')
        ->toContain("root: {$this->docsDir}")
        ->toContain('laravel-docs:{{cache.md,routing.md}}');
});

it('excludes common non-doc files', function (): void {
    file_put_contents($this->basePath.'/laravel-docs/routing.md', '# Routing');
    file_put_contents($this->basePath.'/laravel-docs/README.md', '# Readme');
    file_put_contents($this->basePath.'/laravel-docs/CHANGELOG.md', '# Changes');
    file_put_contents($this->basePath.'/laravel-docs/LICENSE.md', '# License');

    $generator = new IndexGenerator;
    $index = $generator->generate($this->docsDir);

    expect($index)
        ->toContain('routing.md')
        ->not->toContain('README.md')
        ->not->toContain('CHANGELOG.md')
        ->not->toContain('LICENSE.md');
});

it('returns empty string for non-existent directory', function (): void {
    $generator = new IndexGenerator;

    expect($generator->generate('non-existent-dir'))->toBe('');
});

it('filters by repo paths correctly', function (): void {
    file_put_contents($this->basePath.'/laravel-docs/routing.md', '# Routing');
    file_put_contents($this->basePath.'/pestphp-docs/writing-tests.md', '# Tests');

    $generator = new IndexGenerator;
    $index = $generator->generate($this->docsDir, [
        'laravel-docs' => '/',
        'pestphp-docs' => '/',
    ]);

    expect($index)
        ->toContain('routing.md')
        ->toContain('writing-tests.md');
});

it('handles subdirectories in output', function (): void {
    @mkdir($this->basePath.'/laravel-docs/sub', 0755, true);
    file_put_contents($this->basePath.'/laravel-docs/sub/nested.md', '# Nested');

    $generator = new IndexGenerator;
    $index = $generator->generate($this->docsDir);

    expect($index)->toContain('laravel-docs/sub:{{nested.md}}');
});
