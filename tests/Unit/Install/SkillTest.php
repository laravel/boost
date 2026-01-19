<?php

declare(strict_types=1);

use Laravel\Boost\Install\Skill;

it('creates skill with all properties', function (): void {
    $skill = new Skill(
        name: 'building-livewire-components',
        package: 'livewire',
        path: '/path/to/skill',
        description: 'Building reactive components with Livewire',
        custom: false,
    );

    expect($skill->name)->toBe('building-livewire-components')
        ->and($skill->package)->toBe('livewire')
        ->and($skill->path)->toBe('/path/to/skill')
        ->and($skill->description)->toBe('Building reactive components with Livewire')
        ->and($skill->custom)->toBeFalse();
});

it('defaults custom to false', function (): void {
    $skill = new Skill(
        name: 'testing-with-pest',
        package: 'pest',
        path: '/path/to/pest-skill',
        description: 'Testing PHP applications with Pest',
    );

    expect($skill->custom)->toBeFalse();
});

it('can be marked as custom', function (): void {
    $skill = new Skill(
        name: 'my-custom-skill',
        package: 'user',
        path: '/path/to/custom',
        description: 'User custom skill',
        custom: true,
    );

    expect($skill->custom)->toBeTrue();
});

it('returns new instance with custom flag using withCustom', function (): void {
    $skill = new Skill(
        name: 'testing-skill',
        package: 'pest',
        path: '/path/to/skill',
        description: 'A testing skill',
        custom: false,
    );

    $customSkill = $skill->withCustom(true);

    expect($customSkill)->not->toBe($skill)
        ->and($customSkill->custom)->toBeTrue()
        ->and($skill->custom)->toBeFalse()
        ->and($customSkill->name)->toBe('testing-skill')
        ->and($customSkill->package)->toBe('pest')
        ->and($customSkill->path)->toBe('/path/to/skill')
        ->and($customSkill->description)->toBe('A testing skill');
});

it('displays name without asterisk when not custom', function (): void {
    $skill = new Skill(
        name: 'building-livewire-components',
        package: 'livewire',
        path: '/path/to/skill',
        description: 'Building reactive components',
        custom: false,
    );

    expect($skill->displayName())->toBe('building-livewire-components');
});

it('displays name with .ai/ prefix and asterisk when custom', function (): void {
    $skill = new Skill(
        name: 'my-custom-skill',
        package: 'user',
        path: '/path/to/custom',
        description: 'Custom skill',
        custom: true,
    );

    expect($skill->displayName())->toBe('.ai/my-custom-skill*');
});
