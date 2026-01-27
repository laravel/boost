<?php

use Laravel\Boost\Support\Config;

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

it('may store and retrieve herd mcp installation status', function (): void {
    $config = new Config;

    expect($config->getHerdMcp())->toBeFalse();

    $config->setHerdMcp(true);

    expect($config->getHerdMcp())->toBeTrue();

    $config->setHerdMcp(false);

    expect($config->getHerdMcp())->toBeFalse();
});

it('may store and retrieve valet status', function (): void {
    $config = new Config;

    expect($config->getValet())->toBeFalse();

    $config->setValet(true);

    expect($config->getValet())->toBeTrue();

    $config->setValet(false);

    expect($config->getValet())->toBeFalse();
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
