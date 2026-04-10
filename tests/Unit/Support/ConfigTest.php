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

it('may store and retrieve mcp servers', function (): void {
    $config = new Config;

    expect($config->getMcpServers())->toBeEmpty();

    $servers = [
        'acme/my-package/my-server',
        'acme/my-package/my-remote-server',
    ];

    $config->setMcpServers($servers);

    expect($config->getMcpServers())->toEqual($servers);

    $config->setMcpServers([]);

    expect($config->getMcpServers())->toBeEmpty();
});

it('setMcpServers does not affect other config keys', function (): void {
    $config = new Config;

    $config->setMcp(true);
    $config->setSail(true);
    $config->setNightwatchMcp(true);
    $config->setGuidelines(true);
    $config->setPackages(['vendor/pkg']);
    $config->setAgents(['cursor']);

    $config->setMcpServers(['acme/pkg/server']);

    expect($config->getMcp())->toBeTrue()
        ->and($config->getSail())->toBeTrue()
        ->and($config->getNightwatchMcp())->toBeTrue()
        ->and($config->getGuidelines())->toBeTrue()
        ->and($config->getPackages())->toEqual(['vendor/pkg'])
        ->and($config->getAgents())->toEqual(['cursor'])
        ->and($config->getMcpServers())->toEqual(['acme/pkg/server']);
});
