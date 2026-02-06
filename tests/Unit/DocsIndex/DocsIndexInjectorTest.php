<?php

use Laravel\Boost\DocsIndex\DocsIndexInjector;

beforeEach(function (): void {
    $this->testFile = 'test-inject-'.uniqid().'.md';
    $this->testFilePath = base_path($this->testFile);
});

afterEach(function (): void {
    if (file_exists($this->testFilePath)) {
        @unlink($this->testFilePath);
    }
});

it('injects into a new file', function (): void {
    $injector = new DocsIndexInjector;
    $injector->inject($this->testFile, 'test-index-content');

    $content = file_get_contents($this->testFilePath);

    expect($content)
        ->toContain('<!-- LARAVEL-DOCS-INDEX:START -->')
        ->toContain('test-index-content')
        ->toContain('<!-- LARAVEL-DOCS-INDEX:END -->');
});

it('prepends before existing content', function (): void {
    file_put_contents($this->testFilePath, 'existing content here');

    $injector = new DocsIndexInjector;
    $injector->inject($this->testFile, 'index-content');

    $content = file_get_contents($this->testFilePath);

    expect($content)
        ->toStartWith('<!-- LARAVEL-DOCS-INDEX:START -->')
        ->toContain('existing content here');

    // Ensure index comes before existing content
    $indexPos = strpos($content, 'index-content');
    $existingPos = strpos($content, 'existing content here');
    expect($indexPos)->toBeLessThan($existingPos);
});

it('replaces existing block in-place (idempotent)', function (): void {
    $injector = new DocsIndexInjector;

    $injector->inject($this->testFile, 'first-index');
    $injector->inject($this->testFile, 'second-index');

    $content = file_get_contents($this->testFilePath);

    expect($content)
        ->toContain('second-index')
        ->not->toContain('first-index');

    // Only one START marker should exist
    expect(substr_count($content, '<!-- LARAVEL-DOCS-INDEX:START -->'))->toBe(1);
});

it('preserves content after block', function (): void {
    file_put_contents($this->testFilePath, "<laravel-boost-guidelines>\nsome guidelines\n</laravel-boost-guidelines>\n");

    $injector = new DocsIndexInjector;
    $injector->inject($this->testFile, 'index-content');

    $content = file_get_contents($this->testFilePath);

    expect($content)
        ->toContain('index-content')
        ->toContain('<laravel-boost-guidelines>');
});
