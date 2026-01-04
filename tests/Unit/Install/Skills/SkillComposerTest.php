<?php

declare(strict_types=1);

use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Install\Skills\SkillComposer;
use Laravel\Roster\Roster;

beforeEach(function (): void {
    $this->roster = Mockery::mock(Roster::class);
    $this->herd = Mockery::mock(Herd::class);
    $this->guidelineComposer = new GuidelineComposer($this->roster, $this->herd);
    $this->skillComposer = new SkillComposer($this->roster, $this->guidelineComposer);
});

test('extracts YAML frontmatter from content', function (): void {
    $content = <<<'BLADE'
---
name: boost-testing
description: Testing guidelines
---

Write tests for all code.
BLADE;

    $reflection = new ReflectionClass($this->skillComposer);
    $method = $reflection->getMethod('extractFrontmatter');
    $method->setAccessible(true);

    $frontmatter = $method->invoke($this->skillComposer, $content);

    expect($frontmatter)
        ->toHaveKey('name', 'boost-testing')
        ->toHaveKey('description', 'Testing guidelines');
});

test('removes frontmatter before rendering', function (): void {
    $content = <<<'BLADE'
---
name: boost-testing
description: Testing
---

Content after frontmatter.
BLADE;

    $reflection = new ReflectionClass($this->skillComposer);
    $method = $reflection->getMethod('removeFrontmatter');
    $method->setAccessible(true);

    $result = $method->invoke($this->skillComposer, $content);

    expect($result)
        ->not->toContain('name: boost-testing')
        ->toContain('Content after frontmatter.');
});

test('returns empty array for content without frontmatter', function (): void {
    $content = 'Just content, no frontmatter.';

    $reflection = new ReflectionClass($this->skillComposer);
    $method = $reflection->getMethod('extractFrontmatter');
    $method->setAccessible(true);

    expect($method->invoke($this->skillComposer, $content))->toBe([]);
});

test('returns empty array for invalid YAML frontmatter', function (): void {
    $content = <<<'BLADE'
---
invalid: yaml: content: [
---

Content.
BLADE;

    $reflection = new ReflectionClass($this->skillComposer);
    $method = $reflection->getMethod('extractFrontmatter');
    $method->setAccessible(true);

    expect($method->invoke($this->skillComposer, $content))->toBe([]);
});

test('identifies foundation guidelines correctly', function (): void {
    $reflection = new ReflectionClass($this->skillComposer);
    $method = $reflection->getMethod('isFoundationGuideline');
    $method->setAccessible(true);

    expect($method->invoke($this->skillComposer, 'foundation'))->toBeTrue()
        ->and($method->invoke($this->skillComposer, 'boost'))->toBeTrue()
        ->and($method->invoke($this->skillComposer, 'laravel'))->toBeFalse()
        ->and($method->invoke($this->skillComposer, 'pest'))->toBeFalse();
});
