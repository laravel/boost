<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Mcp\Tools\RecordRule;
use Laravel\Boost\Rules\RuleRepository;
use Laravel\Mcp\Request;

/**
 * @return array<int, string>
 */
function ruleFiles(string $directory): array
{
    return array_values(array_filter(
        glob($directory.'/*.md') ?: [],
        fn (string $file): bool => basename($file) !== 'index.md',
    ));
}

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
    $response = (new RecordRule($this->repository))->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'title' => 'Extend BaseController for tenant scoping',
        'note' => 'New controllers must extend App\Http\Controllers\BaseController.',
    ]));

    expect($response)->isToolResult()->toolHasNoError()
        ->toolTextContains('controllers.md', 'Extend BaseController for tenant scoping');

    expect(File::get($this->rulesDir.'/controllers.md'))
        ->toContain('paths:')
        ->toContain('app/Http/Controllers/**')
        ->toContain('# Controllers')
        ->toContain('## Extend BaseController for tenant scoping');
});

it('groups a second rule for the same glob into the same file', function (): void {
    $tool = new RecordRule($this->repository);

    $tool->handle(new Request(['glob' => 'app/Http/Controllers/**', 'title' => 'First', 'note' => 'One.']));
    $tool->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'title' => 'Form Requests over inline validation',
        'note' => 'Validate in Form Request classes.',
    ]));

    expect(ruleFiles($this->rulesDir))->toHaveCount(1);
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

    expect(ruleFiles($this->rulesDir))->toHaveCount(1);
    expect(File::get($this->rulesDir.'/models.md'))
        ->toContain('app/Models/**')
        ->toContain('app/Models/*.php')
        ->toContain('## First model note')
        ->toContain('Keep models thin.')
        ->toContain('## Second model note')
        ->toContain('Watch the users table.');
});

it('keeps distinct areas that share a last path segment in separate files', function (): void {
    $tool = new RecordRule($this->repository);

    $tool->handle(new Request(['glob' => 'app/Admin/Controllers/**', 'title' => 'Admin rule', 'note' => 'Admin only.']));
    $tool->handle(new Request(['glob' => 'app/Api/Controllers/**', 'title' => 'Api rule', 'note' => 'Api only.']));

    expect(ruleFiles($this->rulesDir))->toHaveCount(2);

    expect(File::get($this->rulesDir.'/controllers.md'))
        ->toContain('app/Admin/Controllers/**')
        ->toContain('## Admin rule')
        ->not->toContain('app/Api/Controllers/**')
        ->not->toContain('## Api rule');

    expect(File::get($this->rulesDir.'/api-controllers.md'))
        ->toContain('app/Api/Controllers/**')
        ->toContain('## Api rule')
        ->not->toContain('app/Admin/Controllers/**')
        ->not->toContain('## Admin rule');
});

it('normalizes an absolute path glob to the same area as its relative twin', function (): void {
    $tool = new RecordRule($this->repository);

    $tool->handle(new Request(['glob' => 'app/Http/Controllers/**', 'title' => 'Relative', 'note' => 'a']));
    $tool->handle(new Request([
        'glob' => base_path('app/Http/Controllers/**'),
        'title' => 'Absolute',
        'note' => 'b',
    ]));

    expect(ruleFiles($this->rulesDir))->toHaveCount(1);
    expect(File::get($this->rulesDir.'/controllers.md'))
        ->toContain('## Relative')
        ->toContain('## Absolute')
        ->not->toContain(base_path('app/Http/Controllers/**'));
});

it('does not record a rule into the reserved index file', function (): void {
    $located = $this->repository->write('index/**', 'Index area rule', 'This must survive.');

    expect(basename((string) $located))->not->toBe('index.md');

    expect(File::get($this->rulesDir.'/index.md'))
        ->toContain('# Project Rules Index')
        ->toContain('index/**')
        ->not->toContain('This must survive.');

    expect(File::get($located))
        ->toContain('## Index area rule')
        ->toContain('This must survive.');
});

it('rejects a rule with a missing glob, title, or note', function (string $missing): void {
    $arguments = ['glob' => 'app/**', 'title' => 'A title', 'note' => 'A note'];
    $arguments[$missing] = '';

    $response = (new RecordRule($this->repository))->handle(new Request($arguments));

    expect($response)->isToolResult()->toolHasError()
        ->toolTextContains('non-empty glob, title, and note', 'Missing or empty: '.$missing.'.');
})->with(['glob', 'title', 'note']);

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

    expect(ruleFiles($this->rulesDir))->toHaveCount(2);
    expect(File::get($this->rulesDir.'/index.md'))
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

it('writes a placeholder index when no rule files have paths', function (): void {
    $this->repository->writeIndex();

    expect(File::get($this->rulesDir.'/index.md'))
        ->toContain('# Project Rules Index')
        ->toContain('No rules recorded yet.')
        ->not->toContain('| Applies to |');
});

it('keeps multi-byte characters intact when writing and when merging a new glob', function (): void {
    $title = 'Encoding check: х ゅ 🙅 ✅';
    $note = 'These all contain 0x85: х (U+0445), ゅ (U+3085), 🙅 (U+1F645), ✅ (U+2705).';

    $tool = new RecordRule($this->repository);
    $file = $this->rulesDir.'/models.md';

    $response = $tool->handle(new Request([
        'glob' => 'app/Models/**',
        'title' => $title,
        'note' => $note,
    ]));

    expect($response)->isToolResult()->toolHasNoError();
    expect(mb_check_encoding(File::get($file), 'UTF-8'))->toBeTrue();

    $tool->handle(new Request([
        'glob' => 'app/Models/*.php',
        'title' => 'Second model note',
        'note' => 'Watch the users table.',
    ]));

    expect(mb_check_encoding(File::get($file), 'UTF-8'))->toBeTrue();
    expect(File::get($file))
        ->toContain('## '.$title)
        ->toContain($note);
});
