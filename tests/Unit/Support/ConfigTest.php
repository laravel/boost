<?php

use Laravel\Boost\Support\Config;

beforeEach(function (): void {
    (new Config)->flush();
});

afterEach(function (): void {
    (new Config)->flush();
});

it('may store and retrieve guidelines status', function (): void {
    $config = new Config;

    expect($config->getGuidelines())->toBeFalse();

    $config->setGuidelines(true);

    expect($config->getGuidelines())->toBeTrue();

    $config->setGuidelines(false);

    expect($config->getGuidelines())->toBeFalse();
});

it('may store and retrieve agents', function (): void {
    $config = new Config;

    expect($config->getAgents())->toBeEmpty();

    $agents = [
        'agent_1',
        'agent_2',
    ];

    $config->setAgents($agents);

    expect($config->getAgents())->toEqual($agents);
});

it('may store and retrieve skills as an array', function (): void {
    $config = new Config;

    expect($config->getSkills())->toBeEmpty()
        ->and($config->hasSkills())->toBeFalse();

    $skills = [
        'skill-one',
        'skill-two',
    ];

    $config->setSkills($skills);

    expect($config->getSkills())->toEqual($skills)
        ->and($config->hasSkills())->toBeTrue();

    $config->setSkills([]);

    expect($config->getSkills())->toBeEmpty()
        ->and($config->hasSkills())->toBeFalse();
});

it('may store and retrieve mcp status', function (): void {
    $config = new Config;

    expect($config->getMcp())->toBeFalse();

    $config->setMcp(true);

    expect($config->getMcp())->toBeTrue();

    $config->setMcp(false);

    expect($config->getMcp())->toBeFalse();
});

it('may store and retrieve packages', function (): void {
    $config = new Config;

    expect($config->getPackages())->toBeEmpty();

    $packages = [
        'laravel/fortify',
        'laravel/prism',
    ];

    $config->setPackages($packages);

    expect($config->getPackages())->toEqual($packages);
});

it('may track and query skills across repositories', function (): void {
    $config = new Config;

    expect($config->getTrackedSkills())->toBeEmpty();

    $config->trackSkill('vercel-labs/agent-skills', 'composition-patterns', 'github');
    $config->trackSkill(
        repository: 'vercel-labs/agent-skills',
        skillName: 'deploy-to-vercel',
        sourceType: 'github',
        computedHash: 'bf90d0a4',
    );
    $config->trackSkill('anthropics/skills', 'algorithmic-art', 'github');

    $tracked = $config->getTrackedSkills();

    expect($tracked)->toHaveKeys(['vercel-labs/agent-skills', 'anthropics/skills'])
        ->and($tracked['vercel-labs/agent-skills']['sourceType'])->toBe('github')
        ->and($tracked['vercel-labs/agent-skills']['skills'])->toHaveKeys(['composition-patterns', 'deploy-to-vercel'])
        ->and($tracked['vercel-labs/agent-skills']['skills']['deploy-to-vercel']['computedHash'])->toBe('bf90d0a4');
});
