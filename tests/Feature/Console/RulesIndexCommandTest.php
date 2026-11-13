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

it('indexes rule files grouped in sub-folders', function (): void {
    File::ensureDirectoryExists($this->rulesDir.'/billing/providers');
    File::ensureDirectoryExists($this->rulesDir.'/boost');
    File::put($this->rulesDir.'/models.md', "---\npaths:\n  - app/Models/**\n---\n\n# Models\n");
    File::put($this->rulesDir.'/billing/invoices.md', "---\npaths:\n  - app/Billing/Invoices/**\n---\n\n# Invoices\n");
    File::put($this->rulesDir.'/billing/providers/stripe.md', "---\npaths:\n  - app/Billing/Stripe/**\n---\n\n# Stripe\n");
    File::put($this->rulesDir.'/boost/tests.md', "---\npaths:\n  - tests/**\n---\n\n# Tests\n");
    File::ensureDirectoryExists($this->rulesDir.'/billing/boost');
    File::put($this->rulesDir.'/billing/boost/limits.md', "---\npaths:\n  - app/Billing/Limits/**\n---\n\n# Limits\n");
    File::put($this->rulesDir.'/billing/notes.txt', "---\npaths:\n  - app/Notes/**\n---\n");

    $this->artisan('boost:index-rules')->assertSuccessful();

    $index = File::get($this->rulesDir.'/index.md');

    expect($index)
        ->toContain('| app/Models/** | .ai/rules/models.md |')
        ->toContain('| app/Billing/Invoices/** | .ai/rules/billing/invoices.md |')
        ->toContain('| app/Billing/Stripe/** | .ai/rules/billing/providers/stripe.md |')
        ->toContain('| app/Billing/Limits/** | .ai/rules/billing/boost/limits.md |')
        ->toContain('| tests/** | .ai/rules/boost/tests.md |')
        ->not->toContain('notes.txt')
        ->and(substr_count($index, '.ai/rules/boost/tests.md'))->toBe(1);
});

it('warns about nested markdown without paths frontmatter, including a nested index', function (): void {
    File::ensureDirectoryExists($this->rulesDir.'/billing');
    File::put($this->rulesDir.'/billing/invoices.md', "---\npaths:\n  - app/Billing/**\n---\n\n# Invoices\n");
    File::put($this->rulesDir.'/billing/index.md', "# Billing\n");

    $this->artisan('boost:index-rules')
        ->assertSuccessful()
        ->expectsOutputToContain('Skipped .ai/rules/billing/index.md: no valid `paths` frontmatter.');

    expect(File::get($this->rulesDir.'/index.md'))->toContain('| app/Billing/** | .ai/rules/billing/invoices.md |');
});

it('refuses to regenerate while a nested rule file contains conflict markers', function (): void {
    File::ensureDirectoryExists($this->rulesDir.'/billing');
    File::put($this->rulesDir.'/billing/invoices.md', "---\npaths:\n  - app/Billing/**\n---\n\n# Invoices\n<<<<<<< HEAD\n");
    File::put($this->rulesDir.'/index.md', 'original');

    $this->artisan('boost:index-rules')
        ->assertFailed()
        ->expectsOutputToContain('.ai/rules/billing/invoices.md');

    expect(File::get($this->rulesDir.'/index.md'))->toBe('original');
});

it('skips sub-folders it cannot read', function (): void {
    File::ensureDirectoryExists($this->rulesDir.'/locked');
    File::put($this->rulesDir.'/models.md', "---\npaths:\n  - app/Models/**\n---\n\n# Models\n");
    chmod($this->rulesDir.'/locked', 0000);

    try {
        $this->artisan('boost:index-rules')->assertSuccessful()->run();
    } finally {
        chmod($this->rulesDir.'/locked', 0755);
    }

    expect(File::get($this->rulesDir.'/index.md'))->toContain('| app/Models/** | .ai/rules/models.md |');
})->skipOnWindows()->skip(fn (): bool => function_exists('posix_getuid') && posix_getuid() === 0, 'Root can read any directory.');
