<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Mcp\Tools\RecordRule;
use Laravel\Boost\Rules\RuleRepository;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->rulesBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-rules-test-'.uniqid();

    File::makeDirectory($this->rulesBasePath, 0755, true);
    $this->app->setBasePath($this->rulesBasePath);

    $this->rulesDir = base_path('.ai/rules');

    $this->repository = new RuleRepository($this->rulesDir);
    $this->app->instance(RuleRepository::class, $this->repository);
});

afterEach(function (): void {
    File::deleteDirectory($this->rulesBasePath);
    $this->app->setBasePath($this->originalBasePath);
});

it('writes a rule into an area file with frontmatter and an entry', function (): void {
    $tool = new RecordRule($this->repository);

    $response = $tool->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'title' => 'Extend BaseController for tenant scoping',
        'note' => 'New controllers must extend App\Http\Controllers\BaseController.',
    ]));

    expect($response)->isToolResult()->toolHasNoError()
        ->toolTextContains('controllers.md', 'Extend BaseController for tenant scoping');

    $file = $this->rulesDir.'/controllers.md';
    expect(File::exists($file))->toBeTrue();

    $contents = File::get($file);
    expect($contents)
        ->toContain('paths:')
        ->toContain('app/Http/Controllers/**')
        ->toContain('# Controllers')
        ->toContain('## Extend BaseController for tenant scoping');
});

it('groups a second rule for the same glob into the same file', function (): void {
    $tool = new RecordRule($this->repository);

    $tool->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'title' => 'First',
        'note' => 'One.',
    ]));

    $tool->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'title' => 'Form Requests over inline validation',
        'note' => 'Validate in Form Request classes.',
    ]));

    $files = glob($this->rulesDir.'/*.md');
    $files = array_filter($files, fn (string $f): bool => basename($f) !== 'index.md');

    expect($files)->toHaveCount(1);
    expect(File::get($this->rulesDir.'/controllers.md'))
        ->toContain('## First')
        ->toContain('## Form Requests over inline validation');
});

it('merges a new glob into an existing area file without losing entries', function (): void {
    $tool = new RecordRule($this->repository);

    $tool->handle(new Request([
        'glob' => 'app/Models/**',
        'title' => 'First model note',
        'note' => 'Keep models thin.',
    ]));

    // A different glob that derives the same file name (models.md).
    $tool->handle(new Request([
        'glob' => 'app/Models/*.php',
        'title' => 'Second model note',
        'note' => 'Watch the users table.',
    ]));

    $files = glob($this->rulesDir.'/*.md');
    $files = array_filter($files, fn (string $f): bool => basename($f) !== 'index.md');

    expect($files)->toHaveCount(1);

    $contents = File::get($this->rulesDir.'/models.md');
    expect($contents)
        ->toContain('app/Models/**')
        ->toContain('app/Models/*.php')
        ->toContain('## First model note')
        ->toContain('Keep models thin.')
        ->toContain('## Second model note')
        ->toContain('Watch the users table.');
});

it('routes different globs to their own area files', function (): void {
    $tool = new RecordRule($this->repository);

    $tool->handle(new Request(['glob' => 'app/Http/Controllers/**', 'title' => 'A', 'note' => 'a']));
    $tool->handle(new Request(['glob' => 'app/Models/*.php', 'title' => 'B', 'note' => 'b']));

    expect(File::exists($this->rulesDir.'/controllers.md'))->toBeTrue();
    expect(File::exists($this->rulesDir.'/models.md'))->toBeTrue();
});

it('rejects a rule with a missing glob, title, or note', function (): void {
    $response = (new RecordRule($this->repository))->handle(new Request([
        'glob' => 'app/**',
        'title' => '',
        'note' => 'y',
    ]));

    expect($response)->isToolResult()->toolHasError()
        ->toolTextContains('non-empty glob, title, and note');
});

it('is gated by the rules enabled config flag', function (): void {
    config()->set('boost.rules.enabled', false);
    expect((new RecordRule($this->repository))->shouldRegister())->toBeFalse();

    config()->set('boost.rules.enabled', true);
    expect((new RecordRule($this->repository))->shouldRegister())->toBeTrue();
});

it('regenerates index.md mapping globs to rule files on every write', function (): void {
    $tool = new RecordRule($this->repository);

    $tool->handle(new Request(['glob' => 'app/Http/Controllers/**', 'title' => 'A', 'note' => 'a']));
    $tool->handle(new Request(['glob' => 'app/Models/*.php', 'title' => 'B', 'note' => 'b']));

    $index = $this->rulesDir.'/index.md';
    expect(File::exists($index))->toBeTrue();
    expect(File::get($index))
        ->toContain('app/Http/Controllers/**')
        ->toContain('.ai/rules/controllers.md')
        ->toContain('app/Models/*.php')
        ->toContain('.ai/rules/models.md');
});

it('excludes a rule file with no paths frontmatter from the index', function (): void {
    File::makeDirectory($this->rulesDir, 0755, true);
    File::put($this->rulesDir.'/global.md', "# Global\n\n## Always use transactions\nWrap DB writes in a transaction.\n");

    (new RecordRule($this->repository))->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'title' => 'A',
        'note' => 'a',
    ]));

    expect(File::get($this->rulesDir.'/index.md'))
        ->toContain('.ai/rules/controllers.md')
        ->not->toContain('.ai/rules/global.md')
        ->not->toContain('entire project');
});

it('normalizes an absolute path glob to the same area as its relative twin', function (): void {
    $tool = new RecordRule($this->repository);

    $tool->handle(new Request(['glob' => 'app/Http/Controllers/**', 'title' => 'Relative', 'note' => 'a']));
    $tool->handle(new Request([
        'glob' => base_path('app/Http/Controllers/**'),
        'title' => 'Absolute',
        'note' => 'b',
    ]));

    $files = glob($this->rulesDir.'/*.md');
    $files = array_filter($files, fn (string $f): bool => basename($f) !== 'index.md');

    expect($files)->toHaveCount(1);
    expect(File::get($this->rulesDir.'/controllers.md'))
        ->toContain('## Relative')
        ->toContain('## Absolute')
        ->not->toContain(base_path('app/Http/Controllers/**'));
});

it('writes a placeholder index when no rule files have paths', function (): void {
    $this->repository->writeIndex();

    expect(File::get($this->rulesDir.'/index.md'))
        ->toContain('# Project Rules Index')
        ->toContain('No rules recorded yet.')
        ->not->toContain('| Applies to |');
});
