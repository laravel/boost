<?php

declare(strict_types=1);

use Laravel\Boost\Install\Skill;

test('creates a skill with required properties', function (): void {
    $skill = new Skill(
        name: 'boost-testing',
        description: 'Testing guidelines',
        content: 'Write tests for all code.',
    );

    expect($skill->name)->toBe('boost-testing')
        ->and($skill->description)->toBe('Testing guidelines')
        ->and($skill->content)->toBe('Write tests for all code.')
        ->and($skill->metadata)->toBe([]);
});

test('generates valid SKILL.md format via toSkillMd', function (): void {
    $skill = new Skill(
        name: 'boost-testing',
        description: 'Testing guidelines',
        content: 'Write tests for all code.',
    );

    expect($skill->toSkillMd())
        ->toContain('---')
        ->toContain('name: boost-testing')
        ->toContain('description: Testing guidelines')
        ->toContain('Write tests for all code.');
});

test('escapes special YAML characters in description', function (): void {
    $skill = new Skill(
        name: 'boost-testing',
        description: 'Use: colons and # hashes',
        content: 'Content here.',
    );

    expect($skill->toSkillMd())
        ->toContain('description: "Use: colons and # hashes"');
});

test('escapes quotes in description when combined with special characters', function (): void {
    // Quotes alone don't trigger escaping, but quotes + colon do
    $skill = new Skill(
        name: 'boost-testing',
        description: 'Rule: Use "quotes" properly',
        content: 'Content here.',
    );

    expect($skill->toSkillMd())
        ->toContain('description: "Rule: Use \\"quotes\\" properly"');
});

test('includes metadata in frontmatter when provided', function (): void {
    $skill = new Skill(
        name: 'boost-testing',
        description: 'Testing',
        content: 'Content',
        metadata: ['version' => '1.0', 'author' => 'Boost'],
    );

    expect($skill->toSkillMd())
        ->toContain('metadata:')
        ->toContain('  version: 1.0')
        ->toContain('  author: Boost');
});

test('returns directory name matching skill name', function (): void {
    $skill = new Skill(
        name: 'boost-testing',
        description: 'Testing',
        content: 'Content',
    );

    expect($skill->directoryName())->toBe('boost-testing');
});

test('estimates tokens based on name and description only', function (): void {
    // Skills are lazy-loaded, so only name + description count
    // "boost-laravel" + "Laravel framework guidelines" = 4 words × 1.3 ≈ 5
    $skill = new Skill(
        name: 'boost-laravel',
        description: 'Laravel framework guidelines',
        content: 'This content is not counted because skills are lazy-loaded.',
    );

    expect($skill->estimatedTokens())->toBe(5);
});

test('nameFromGuidelineKey maps guideline keys to skill names', function (string $guidelineKey, string $expectedSkillName): void {
    expect(Skill::nameFromGuidelineKey($guidelineKey))->toBe($expectedSkillName);
})->with([
    // Base /core guidelines strip the suffix
    'laravel/core' => ['laravel/core', 'boost-laravel'],
    'pest/core' => ['pest/core', 'boost-pest'],
    'livewire/core' => ['livewire/core', 'boost-livewire'],
    'tailwindcss/core' => ['tailwindcss/core', 'boost-tailwindcss'],
    'pint/core' => ['pint/core', 'boost-pint'],

    // Version-specific guidelines map to version-specific skills
    'laravel/v12' => ['laravel/v12', 'boost-laravel-12'],
    'pest/v4' => ['pest/v4', 'boost-pest-4'],
    'tailwindcss/v4' => ['tailwindcss/v4', 'boost-tailwindcss-4'],

    // Version-specific guidelines produce version-specific names (matching is handled separately)
    'livewire/v3' => ['livewire/v3', 'boost-livewire-3'],

    // Nested paths with slashes
    'inertia-laravel/v2' => ['inertia-laravel/v2', 'boost-inertia-laravel-2'],
    'inertia-react/v2/forms' => ['inertia-react/v2/forms', 'boost-inertia-react-2-forms'],

    // Third-party package guidelines
    'filament/filament' => ['filament/filament', 'boost-filament-filament'],
]);
