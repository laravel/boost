<?php

declare(strict_types=1);

use Laravel\Boost\Install\Skill;

test('it creates skill with all properties', function (): void {
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

test('it defaults custom to false', function (): void {
    $skill = new Skill(
        name: 'testing-with-pest',
        package: 'pest',
        path: '/path/to/pest-skill',
        description: 'Testing PHP applications with Pest',
    );

    expect($skill->custom)->toBeFalse();
});

test('it can be marked as custom', function (): void {
    $skill = new Skill(
        name: 'my-custom-skill',
        package: 'user',
        path: '/path/to/custom',
        description: 'User custom skill',
        custom: true,
    );

    expect($skill->custom)->toBeTrue();
});
