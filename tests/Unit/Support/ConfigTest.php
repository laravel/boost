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

it('may track and query skills with source metadata', function (): void {
    $config = new Config;

    expect($config->getTrackedSkills())->toBeEmpty();

    $config->trackSkill('composition-patterns', 'vercel-labs/agent-skills');
    $config->trackSkill('algorithmic-art', 'anthropics/skills');

    $tracked = $config->getTrackedSkills();

    expect($tracked)->toHaveKeys(['composition-patterns', 'algorithmic-art'])
        ->and($tracked['composition-patterns']['source'])->toBe('vercel-labs/agent-skills')
        ->and($tracked['algorithmic-art']['source'])->toBe('anthropics/skills');
});

it('keeps tracked source metadata when syncing skills list', function (): void {
    $config = new Config;

    $config->setSkills(['algorithmic-art']);
    $config->trackSkill('algorithmic-art', 'anthropics/skills');
    $config->setSkills(['algorithmic-art', 'pest-testing']);

    expect($config->getSkills())->toBe(['algorithmic-art', 'pest-testing']);

    $tracked = $config->getTrackedSkills();

    expect($tracked)->toHaveKey('algorithmic-art')
        ->and($tracked['algorithmic-art']['source'])->toBe('anthropics/skills')
        ->and($tracked)->not->toHaveKey('pest-testing');
});

it('normalizes legacy list-format skills into grouped format', function (): void {
    file_put_contents(base_path('boost.json'), json_encode([
        'agents' => ['claude_code'],
        'skills' => ['pest-testing', 'fortify-development'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $config = new Config;

    expect($config->getSkills())->toBe(['pest-testing', 'fortify-development']);

    $config->setSkills($config->getSkills());

    $normalized = json_decode((string) file_get_contents(base_path('boost.json')), true);

    expect($normalized['skills'])->toBe([
        'laravel/boost' => ['fortify-development', 'pest-testing'],
    ]);
});

it('reads legacy flat-format skill source metadata', function (): void {
    file_put_contents(base_path('boost.json'), json_encode([
        'agents' => ['claude_code'],
        'skills' => [
            'copilot-docs' => ['source' => 'anthropics/skills'],
            'pest-testing' => ['source' => ''],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $config = new Config;
    $tracked = $config->getTrackedSkills();

    expect($config->getSkills())->toBe(['copilot-docs', 'pest-testing'])
        ->and($tracked)->toHaveKey('copilot-docs')
        ->and($tracked['copilot-docs']['source'])->toBe('anthropics/skills')
        ->and($tracked)->not->toHaveKey('pest-testing');
});
