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
        ->and($config->hasSkills())->toBeTrue()
        ->and($config->getSkillMetadata())->toBe([
            'skill-one' => ['source' => 'official'],
            'skill-two' => ['source' => 'official'],
        ]);

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

it('may store and retrieve nightwatch status', function (): void {
    $config = new Config;

    expect($config->getNightwatch())->toBeFalse();

    $config->setNightwatch(true);

    expect($config->getNightwatch())->toBeTrue();
});

it('reads the legacy nightwatch_mcp key when the new nightwatch key is absent', function (): void {
    file_put_contents(base_path('boost.json'), json_encode(['nightwatch_mcp' => true]));

    expect((new Config)->getNightwatch())->toBeTrue();
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

    $config->trackSkills([
        'composition-patterns' => [
            'source' => 'github',
            'repo' => 'vercel-labs/agent-skills',
        ],
        'algorithmic-art' => [
            'source' => 'github',
            'repo' => 'anthropics/skills',
        ],
    ]);

    $tracked = $config->getTrackedSkills();

    expect($tracked)->toHaveKeys(['composition-patterns', 'algorithmic-art'])
        ->and($tracked['composition-patterns'])->toBe([
            'source' => 'github',
            'repo' => 'vercel-labs/agent-skills',
        ])
        ->and($tracked['algorithmic-art'])->toBe([
            'source' => 'github',
            'repo' => 'anthropics/skills',
        ]);
});

it('keeps tracked source metadata when syncing skills list', function (): void {
    $config = new Config;

    $config->setSkills(['algorithmic-art']);
    $config->trackSkills([
        'algorithmic-art' => [
            'source' => 'github',
            'repo' => 'anthropics/skills',
        ],
    ]);
    $config->setSkills(['algorithmic-art', 'pest-testing'], [
        'algorithmic-art' => ['source' => 'custom'],
        'pest-testing' => ['source' => 'official'],
    ]);

    expect($config->getSkills())->toBe(['algorithmic-art', 'pest-testing']);

    $tracked = $config->getTrackedSkills();

    expect($tracked['algorithmic-art'])->toBe([
        'source' => 'github',
        'repo' => 'anthropics/skills',
    ])->and($tracked['pest-testing'])->toBe(['source' => 'official']);
});

it('normalizes legacy list-format skills into skill metadata format', function (): void {
    file_put_contents(base_path('boost.json'), json_encode([
        'agents' => ['claude_code'],
        'skills' => ['pest-testing', 'fortify-development'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $config = new Config;

    expect($config->getSkills())->toBe(['pest-testing', 'fortify-development']);

    $config->setSkills($config->getSkills());

    $normalized = json_decode((string) file_get_contents(base_path('boost.json')), true);

    expect($normalized['skills'])->toBe([
        'fortify-development' => ['source' => 'custom'],
        'pest-testing' => ['source' => 'custom'],
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
        ->and($tracked['copilot-docs'])->toBe([
            'source' => 'github',
            'repo' => 'anthropics/skills',
        ])
        ->and($tracked['pest-testing'])->toBe(['source' => 'custom']);
});

it('reads legacy grouped source metadata', function (): void {
    file_put_contents(base_path('boost.json'), json_encode([
        'agents' => ['claude_code'],
        'skills' => [
            'laravel/boost' => ['pest-testing'],
            'owner/repo/path/to/skills' => ['skill-one'],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $config = new Config;

    expect($config->getSkillMetadata())->toBe([
        'pest-testing' => ['source' => 'official'],
        'skill-one' => [
            'source' => 'github',
            'repo' => 'owner/repo',
            'path' => 'path/to/skills/skill-one',
        ],
    ]);
});
