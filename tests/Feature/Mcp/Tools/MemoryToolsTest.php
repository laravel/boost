<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Mcp\Tools\MemorySearch;
use Laravel\Boost\Mcp\Tools\MemoryWrite;
use Laravel\Boost\Memory\MemoryRepository;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    $this->memoryDir = base_path('.ai/memory');
    File::deleteDirectory($this->memoryDir);

    $this->repository = new MemoryRepository($this->memoryDir);
    $this->app->instance(MemoryRepository::class, $this->repository);
});

afterEach(function (): void {
    File::deleteDirectory($this->memoryDir);
});

it('writes a memory into an area file with frontmatter and a tagged entry', function (): void {
    $tool = new MemoryWrite($this->repository);

    $response = $tool->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'type' => 'gotcha',
        'title' => 'Extend BaseController for tenant scoping',
        'note' => 'New controllers must extend App\Http\Controllers\BaseController.',
    ]));

    expect($response)->isToolResult()->toolHasNoError()
        ->toolTextContains('controllers.md', 'Commit this file');

    $file = $this->memoryDir.'/controllers.md';
    expect(File::exists($file))->toBeTrue();

    $contents = File::get($file);
    expect($contents)
        ->toContain('applies_to:')
        ->toContain('app/Http/Controllers/**')
        ->toContain('# Controllers')
        ->toContain('## [gotcha] Extend BaseController for tenant scoping');
});

it('groups a second memory for the same glob into the same file', function (): void {
    $tool = new MemoryWrite($this->repository);

    $tool->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'type' => 'gotcha',
        'title' => 'First',
        'note' => 'One.',
    ]));

    $tool->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'type' => 'decision',
        'title' => 'Form Requests over inline validation',
        'note' => 'Validate in Form Request classes.',
    ]));

    $files = glob($this->memoryDir.'/*.md');
    $files = array_filter($files, fn (string $f): bool => basename($f) !== 'index.md');

    expect($files)->toHaveCount(1);
    expect(File::get($this->memoryDir.'/controllers.md'))
        ->toContain('## [gotcha] First')
        ->toContain('## [decision] Form Requests over inline validation');
});

it('merges a new glob into an existing area file without losing entries', function (): void {
    $tool = new MemoryWrite($this->repository);

    $tool->handle(new Request([
        'glob' => 'app/Models/**',
        'type' => 'decision',
        'title' => 'First model note',
        'note' => 'Keep models thin.',
    ]));

    // A different glob that derives the same file name (models.md).
    $tool->handle(new Request([
        'glob' => 'app/Models/*.php',
        'type' => 'gotcha',
        'title' => 'Second model note',
        'note' => 'Watch the users table.',
    ]));

    $files = glob($this->memoryDir.'/*.md');
    $files = array_filter($files, fn (string $f): bool => basename($f) !== 'index.md');
    expect($files)->toHaveCount(1);

    $contents = File::get($this->memoryDir.'/models.md');
    expect($contents)
        ->toContain('app/Models/**')
        ->toContain('app/Models/*.php')
        ->toContain('## [decision] First model note')
        ->toContain('Keep models thin.')
        ->toContain('## [gotcha] Second model note')
        ->toContain('Watch the users table.');
});

it('routes different globs to their own area files', function (): void {
    $tool = new MemoryWrite($this->repository);

    $tool->handle(new Request(['glob' => 'app/Http/Controllers/**', 'type' => 'gotcha', 'title' => 'A', 'note' => 'a']));
    $tool->handle(new Request(['glob' => 'app/Models/*.php', 'type' => 'decision', 'title' => 'B', 'note' => 'b']));

    expect(File::exists($this->memoryDir.'/controllers.md'))->toBeTrue();
    expect(File::exists($this->memoryDir.'/models.md'))->toBeTrue();
});

it('rejects an invalid type', function (): void {
    $tool = new MemoryWrite($this->repository);

    $response = $tool->handle(new Request([
        'glob' => 'app/**',
        'type' => 'rumour',
        'title' => 'X',
        'note' => 'y',
    ]));

    expect($response)->isToolResult()->toolHasError()
        ->toolTextContains('decision, gotcha, rule');
});

it('finds memories by the path being edited', function (): void {
    (new MemoryWrite($this->repository))->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'type' => 'gotcha',
        'title' => 'Extend BaseController',
        'note' => 'Tenant scoping lives there.',
    ]));

    $response = (new MemorySearch($this->repository))->handle(new Request([
        'path' => 'app/Http/Controllers/OrderController.php',
    ]));

    expect($response)->isToolResult()->toolHasNoError()
        ->toolTextContains('Extend BaseController', 'Tenant scoping lives there.');
});

it('does not return memories for an unrelated path', function (): void {
    (new MemoryWrite($this->repository))->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'type' => 'gotcha',
        'title' => 'Controller thing',
        'note' => 'note',
    ]));

    $response = (new MemorySearch($this->repository))->handle(new Request([
        'path' => 'resources/views/welcome.blade.php',
    ]));

    expect($response)->isToolResult()->toolHasNoError()
        ->toolTextContains('No matching project memory');
});

it('finds memories by keyword', function (): void {
    (new MemoryWrite($this->repository))->handle(new Request([
        'glob' => 'config/**',
        'type' => 'decision',
        'title' => 'Reverb over Pusher',
        'note' => 'We self-host websockets with Reverb.',
    ]));

    $response = (new MemorySearch($this->repository))->handle(new Request([
        'query' => 'reverb',
    ]));

    expect($response)->isToolResult()->toolHasNoError()
        ->toolTextContains('Reverb over Pusher');
});

it('requires at least one search parameter', function (): void {
    $response = (new MemorySearch($this->repository))->handle(new Request([]));

    expect($response)->isToolResult()->toolHasError()
        ->toolTextContains('Provide a "path", a "query", or both.');
});

it('regenerates the glob to file index on write', function (): void {
    (new MemoryWrite($this->repository))->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'type' => 'gotcha',
        'title' => 'A',
        'note' => 'a',
    ]));

    $index = $this->memoryDir.'/index.md';
    expect(File::exists($index))->toBeTrue();
    expect(File::get($index))
        ->toContain('app/Http/Controllers/**')
        ->toContain('[controllers.md](controllers.md)');
});

it('is gated by the memory enabled config flag', function (): void {
    config()->set('boost.memory.enabled', false);
    expect((new MemoryWrite($this->repository))->shouldRegister())->toBeFalse();
    expect((new MemorySearch($this->repository))->shouldRegister())->toBeFalse();

    config()->set('boost.memory.enabled', true);
    expect((new MemoryWrite($this->repository))->shouldRegister())->toBeTrue();
    expect((new MemorySearch($this->repository))->shouldRegister())->toBeTrue();
});
