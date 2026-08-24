<?php

declare(strict_types=1);

use Laravel\Boost\Install\Cloud;
use Laravel\Boost\Skills\Remote\GitHubRepository;

test('builds a GitHubRepository that points at the cloud cli skills directory', function (): void {
    $cloud = new Cloud;

    $repository = GitHubRepository::fromInput($cloud->skillRepo().'/'.$cloud->skillPath());

    expect($repository->owner)->toBe('laravel')
        ->and($repository->repo)->toBe('cloud-cli')
        ->and($repository->path)->toBe('skills')
        ->and($repository->source())->toBe('laravel/cloud-cli/skills');
});

test('targets the deploying-laravel-cloud skill', function (): void {
    $cloud = new Cloud;

    expect($cloud->skillName())->toBe('deploying-laravel-cloud');
});
