<?php

declare(strict_types=1);

use Laravel\Boost\Skills\Remote\RemoteSkill;

it('creates a remote skill with all properties', function (): void {
    $skill = new RemoteSkill(
        name: 'frontend-design',
        repo: 'vercel-labs/agent-skills',
        path: 'frontend-design'
    );

    expect($skill->name)->toBe('frontend-design')
        ->and($skill->repo)->toBe('vercel-labs/agent-skills')
        ->and($skill->path)->toBe('frontend-design');
});
