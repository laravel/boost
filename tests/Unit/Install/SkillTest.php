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
