<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Mcp\Tools\RecordRule;
use Laravel\Boost\Rules\RuleRepository;
use Laravel\Mcp\Request;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->rulesBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-managed-rules-test-'.uniqid();

    File::makeDirectory($this->rulesBasePath, 0755, true);
    $this->app->setBasePath($this->rulesBasePath);

    $this->rulesDir = base_path('.ai/rules');

    $this->repository = new RuleRepository($this->rulesDir);
});

afterEach(function (): void {
    File::deleteDirectory($this->rulesBasePath);
    $this->app->setBasePath($this->originalBasePath);
});

function managedRuleFiles(string ...$slugs): Collection
{
    return collect($slugs)->mapWithKeys(fn (string $slug): array => [$slug => [
        'paths' => ['tests/**'],
        'title' => ucfirst($slug),
        'content' => "Rule body for {$slug}.",
    ]]);
}

it('writes managed rule files under a boost subdirectory with frontmatter', function (): void {
    $written = $this->repository->syncManaged(managedRuleFiles('tests'));

    expect($written)->toHaveCount(1);

    $path = $this->rulesDir.'/boost/tests.md';
    expect(File::exists($path))->toBeTrue();

    $contents = File::get($path);
    expect($contents)
        ->toContain('paths:')
        ->toContain('tests/**')
        ->toContain('# Tests')
        ->toContain('Rule body for tests.');
});

it('wipes and regenerates managed files on every sync', function (): void {
    $this->repository->syncManaged(managedRuleFiles('tests', 'components'));

    expect(File::exists($this->rulesDir.'/boost/tests.md'))->toBeTrue();
    expect(File::exists($this->rulesDir.'/boost/components.md'))->toBeTrue();

    $this->repository->syncManaged(managedRuleFiles('tests'));

    expect(File::exists($this->rulesDir.'/boost/tests.md'))->toBeTrue();
    expect(File::exists($this->rulesDir.'/boost/components.md'))->toBeFalse();
});

it('leaves root user-recorded rule files untouched when syncing managed rules', function (): void {
    (new RecordRule($this->repository))->handle(new Request([
        'glob' => 'app/Http/Controllers/**',
        'title' => 'Team rule',
        'note' => 'A team-recorded rule.',
    ]));

    $this->repository->syncManaged(managedRuleFiles('tests'));

    expect(File::exists($this->rulesDir.'/controllers.md'))->toBeTrue();
    expect(File::get($this->rulesDir.'/controllers.md'))->toContain('Team rule');
    expect(File::exists($this->rulesDir.'/boost/tests.md'))->toBeTrue();
});

it('record-rule never appends into a managed rule file even with a matching glob', function (): void {
    $this->repository->syncManaged(managedRuleFiles('tests'));

    (new RecordRule($this->repository))->handle(new Request([
        'glob' => 'tests/**',
        'title' => 'Team testing note',
        'note' => 'A team addition.',
    ]));

    $managed = File::get($this->rulesDir.'/boost/tests.md');
    expect($managed)->not->toContain('Team testing note');

    $rootFiles = collect(glob($this->rulesDir.'/*.md'))->map(fn (string $f): string => basename($f));
    expect($rootFiles)->toContain('tests.md');
    expect(File::get($this->rulesDir.'/tests.md'))->toContain('Team testing note');
});

it('includes both root and managed rows in the index sorted by path', function (): void {
    (new RecordRule($this->repository))->handle(new Request([
        'glob' => 'app/Models/**',
        'title' => 'Team rule',
        'note' => 'note',
    ]));

    $this->repository->syncManaged(managedRuleFiles('tests'));

    $index = File::get($this->rulesDir.'/index.md');

    expect($index)
        ->toContain('.ai/rules/boost/tests.md')
        ->toContain('.ai/rules/models.md');
});

it('clearManaged removes the managed directory and regenerates the index when root rules remain', function (): void {
    (new RecordRule($this->repository))->handle(new Request([
        'glob' => 'app/Models/**',
        'title' => 'Team rule',
        'note' => 'note',
    ]));

    $this->repository->syncManaged(managedRuleFiles('tests'));

    $removed = $this->repository->clearManaged();

    expect($removed)->toBeTrue();
    expect(File::isDirectory($this->rulesDir.'/boost'))->toBeFalse();

    $index = File::get($this->rulesDir.'/index.md');
    expect($index)
        ->toContain('.ai/rules/models.md')
        ->not->toContain('.ai/rules/boost');
});

it('clearManaged removes the whole rules directory when nothing else remains', function (): void {
    $this->repository->syncManaged(managedRuleFiles('tests'));

    $removed = $this->repository->clearManaged();

    expect($removed)->toBeTrue();
    expect(File::isDirectory($this->rulesDir))->toBeFalse();
});

it('clearManaged is a no-op when there is nothing managed', function (): void {
    expect($this->repository->clearManaged())->toBeFalse();
    expect(File::isDirectory($this->rulesDir))->toBeFalse();
});

it('syncManaged with an empty collection clears any previously managed files', function (): void {
    $this->repository->syncManaged(managedRuleFiles('tests'));
    expect(File::exists($this->rulesDir.'/boost/tests.md'))->toBeTrue();

    $written = $this->repository->syncManaged(collect());

    expect($written)->toBe([]);
    expect(File::isDirectory($this->rulesDir.'/boost'))->toBeFalse();
});

it('syncManaged with an empty collection removes the whole rules directory when nothing else remains', function (): void {
    expect(File::isDirectory($this->rulesDir))->toBeFalse();

    $this->repository->syncManaged(collect());

    expect(File::isDirectory($this->rulesDir))->toBeFalse();
});

it('syncManaged with an empty collection keeps the index when root rules remain', function (): void {
    (new RecordRule($this->repository))->handle(new Request([
        'glob' => 'app/Models/**',
        'title' => 'Team rule',
        'note' => 'note',
    ]));

    $this->repository->syncManaged(collect());

    expect(File::isDirectory($this->rulesDir))->toBeTrue();
    expect(File::get($this->rulesDir.'/index.md'))->toContain('.ai/rules/models.md');
});
