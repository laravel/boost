<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Rules\RuleRepository;

beforeEach(function (): void {
    $this->originalBasePath = base_path();
    $this->rulesBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-rules-index-test-'.uniqid();

    File::makeDirectory($this->rulesBasePath, 0755, true);
    $this->app->setBasePath($this->rulesBasePath);

    $this->rulesDir = base_path('.ai/rules');

    $this->app->instance(RuleRepository::class, new RuleRepository($this->rulesDir));
});

afterEach(function (): void {
    File::deleteDirectory($this->rulesBasePath);
    $this->app->setBasePath($this->originalBasePath);
});

it('regenerates a conflicted index from the rule files', function (): void {
    File::ensureDirectoryExists($this->rulesDir.'/boost');
    File::put($this->rulesDir.'/models.md', "---\npaths:\n  - app/Models/**\n---\n\n# Models\n");
    File::put($this->rulesDir.'/boost/tests.md', "---\npaths:\n  - tests/**\n---\n\n# Tests\n");
    File::put($this->rulesDir.'/index.md', "<<<<<<< HEAD\n| a | b |\n=======\n| c | d |\n>>>>>>> feature\n");

    $this->artisan('boost:index-rules')
        ->assertSuccessful()
        ->expectsOutputToContain('Regenerated .ai/rules/index.md.');

    expect(File::get($this->rulesDir.'/index.md'))
        ->not->toContain('<<<<<<<')
        ->toContain('| app/Models/** | .ai/rules/models.md |')
        ->toContain('| tests/** | .ai/rules/boost/tests.md |');
});

it('refuses to regenerate while rule files contain conflict markers', function (): void {
    File::ensureDirectoryExists($this->rulesDir);
    File::put($this->rulesDir.'/models.md', "---\npaths:\n<<<<<<< HEAD\n  - app/Models/**\n=======\n  - app/Models/*.php\n>>>>>>> feature\n---\n\n# Models\n");
    File::put($this->rulesDir.'/index.md', 'original');

    $this->artisan('boost:index-rules')
        ->assertFailed()
        ->expectsOutputToContain('.ai/rules/models.md');

    expect(File::get($this->rulesDir.'/index.md'))->toBe('original');
});

it('refuses to regenerate while managed rule files contain conflict markers', function (): void {
    File::ensureDirectoryExists($this->rulesDir.'/boost');
    File::put($this->rulesDir.'/boost/tests.md', "---\npaths:\n  - tests/**\n---\n\n# Tests\n>>>>>>> feature\n");

    $this->artisan('boost:index-rules')
        ->assertFailed()
        ->expectsOutputToContain('.ai/rules/boost/tests.md');

    expect(File::exists($this->rulesDir.'/index.md'))->toBeFalse();
});

it('warns about rule files with invalid frontmatter', function (): void {
    File::ensureDirectoryExists($this->rulesDir);
    File::put($this->rulesDir.'/models.md', "---\npaths:\n  - app/Models/**\n paths:\n  - app/*.php\n---\n\n# Models\n");

    $this->artisan('boost:index-rules')
        ->assertSuccessful()
        ->expectsOutputToContain('Skipped .ai/rules/models.md');
});

it('does not mistake a markdown heading underline for a conflict marker', function (): void {
    File::ensureDirectoryExists($this->rulesDir);
    File::put($this->rulesDir.'/models.md', "---\npaths:\n  - app/Models/**\n---\n\nTesting\n=======\n");

    $this->artisan('boost:index-rules')->assertSuccessful();

    expect(File::get($this->rulesDir.'/index.md'))->toContain('| app/Models/** | .ai/rules/models.md |');
});

it('does nothing when there are no project rules', function (): void {
    $this->artisan('boost:index-rules')
        ->assertSuccessful()
        ->expectsOutputToContain('No project rules found');

    expect(File::isDirectory($this->rulesDir))->toBeFalse();
});
