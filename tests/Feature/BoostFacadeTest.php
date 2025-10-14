<?php

declare(strict_types=1);

use Laravel\Boost\Boost;
use Laravel\Boost\BoostManager;
use Tests\Unit\Install\ExampleCodeEnvironment;

it('Boost Facade resolves to BoostManager instance', function (): void {
    $instance = Boost::getFacadeRoot();

    expect($instance)->toBeInstanceOf(BoostManager::class);
});

it('Boost Facade registers code environments via facade', function (): void {
    Boost::registerCodeEnvironment('example1', ExampleCodeEnvironment::class);
    Boost::registerCodeEnvironment('example2', ExampleCodeEnvironment::class);
    $registered = Boost::getFacadeRoot()->getCodeEnvironments();

    expect($registered)->toHaveKey('example1')
        ->and($registered['example1'])->toBe(ExampleCodeEnvironment::class)
        ->and($registered)->toHaveKey('example2')
        ->and($registered['example2'])->toBe(ExampleCodeEnvironment::class)
        ->and($registered)->toHaveKey('phpstorm');
});

it('Boost Facade maintains registration state across facade calls', function (): void {
    Boost::registerCodeEnvironment('persistent', 'Test\Persistent');

    $registered = Boost::getFacadeRoot()->getCodeEnvironments();

    expect($registered)->toHaveKey('persistent')
        ->and($registered['persistent'])->toBe('Test\Persistent');
});
