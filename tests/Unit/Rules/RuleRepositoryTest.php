<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Rules\RuleRepository;

beforeEach(function (): void {
    $this->rulesDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'boost-rules-'.uniqid();
    $this->repository = new RuleRepository($this->rulesDir);
});

afterEach(function (): void {
    File::deleteDirectory($this->rulesDir);
});

it('records the guideline key in frontmatter and exposes it as scoped', function (): void {
    $path = $this->repository->write('tests/**', 'Pest', 'Use Pest for testing.', 'pest/core');

    expect(File::get($path))
        ->toContain("guidelines:\n  - pest/core")
        ->toContain("paths:\n  - 'tests/**'");

    expect($this->repository->scopedGuidelineKeys())->toBe(['pest/core']);
});

it('merges guideline keys into an existing rule file without duplicating', function (): void {
    $this->repository->write('tests/**', 'Testing', 'Write feature tests.');
    $path = $this->repository->write('tests/**', 'Pest', 'Use Pest.', 'pest/core');
    $this->repository->write('tests/**', 'PHPUnit', 'Convert to PHPUnit.', 'phpunit/core');
    $this->repository->write('tests/**', 'Pest', 'Use Pest.', 'pest/core');

    $contents = File::get($path);

    expect(substr_count($contents, 'pest/core'))->toBe(1);
    expect($this->repository->scopedGuidelineKeys())->toBe(['pest/core', 'phpunit/core']);
});

it('reports no scoped guidelines for plain rules', function (): void {
    $this->repository->write('app/Models/**', 'Fillable', 'Use $fillable.');

    expect($this->repository->scopedGuidelineKeys())->toBe([]);
});
